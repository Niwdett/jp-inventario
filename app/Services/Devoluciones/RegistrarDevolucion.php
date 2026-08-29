<?php

namespace App\Services\Devoluciones;

use App\Enums\EstadoDevolucion;
use App\Enums\EstadoVenta;
use App\Enums\TipoMovimiento;
use App\Exceptions\DevolucionInvalidaException;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Models\VentaLinea;
use App\Services\Clientes\MovimientoSaldoFavor;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registro de una devolución de mercancía ya entregada (RF-011; flujo 4.4).
 *
 * Distinta de la anulación (RF-010): esta ocurre **después** de la entrega, la
 * valida el Administrador y genera saldo a favor (nunca efectivo, RN-11).
 *
 * En una transacción (bloque B1), con el mismo orden de bloqueo que el resto
 * (variantes por `id`, luego el cliente):
 *
 * 1. Validar que la venta está entregada y (si se valida) que tiene cliente.
 * 2. Por cada línea a devolver, validar que la cantidad no supera lo que queda
 *    sin devolver de esa `venta_linea`.
 * 3. Crear la devolución y su detalle. Si es `validada`:
 *    - por línea con `reintegra_inventario = true`, reintegrar stock +
 *      movimiento `tipo=devolucion` (RN-13);
 *    - acumular `saldo_generado = Σ(valor_unitario × cantidad)` y abonarlo como
 *      saldo a favor al cliente.
 * 4. Si es `rechazada`, se registra para auditoría sin ningún efecto.
 *
 * `valor_unitario` es lo que el cliente pagó por unidad de esa línea
 * (`venta_lineas.importe_linea / cantidad`); el Administrador no lo edita.
 */
class RegistrarDevolucion
{
    public function __construct(
        private readonly MovimientoStock $movimientoStock,
        private readonly MovimientoSaldoFavor $movimientoSaldoFavor,
    ) {}

    /**
     * @param  list<array{venta_linea_id: int, cantidad: int, reintegra_inventario: bool}>  $lineas
     *
     * @throws DevolucionInvalidaException
     */
    public function ejecutar(
        Venta $venta,
        array $lineas,
        string $motivo,
        EstadoDevolucion $estado,
        Carbon $fecha,
        User $usuario,
    ): Devolucion {
        return DB::transaction(function () use ($venta, $lineas, $motivo, $estado, $fecha, $usuario) {
            /** @var Venta $venta */
            $venta = Venta::whereKey($venta->getKey())->lockForUpdate()->firstOrFail();

            if ($venta->estado !== EstadoVenta::Confirmada || $venta->entregada_at === null) {
                throw DevolucionInvalidaException::ventaNoEntregada($venta);
            }

            $validada = $estado === EstadoDevolucion::Validada;

            if ($validada && $venta->cliente_id === null) {
                throw DevolucionInvalidaException::sinCliente($venta);
            }

            /** @var Collection<int, VentaLinea> $ventaLineas */
            $ventaLineas = $venta->lineas()->with('variante')->get()->keyBy('id');

            $variantes = collect();
            if ($validada) {
                $ids = collect($lineas)->pluck('venta_linea_id')
                    ->map(fn (int $id) => $ventaLineas[$id]?->variante_id)
                    ->filter()->unique()->sort()->values();

                $variantes = Variante::query()
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
            }

            $this->validarCantidades($venta, $lineas, $ventaLineas);

            $devolucion = $venta->devoluciones()->create([
                'usuario_id' => $usuario->id,
                'fecha' => $fecha->toDateString(),
                'estado' => $estado,
                'motivo' => $motivo,
                'saldo_generado' => '0',
            ]);

            $saldoGenerado = '0';

            foreach ($lineas as $linea) {
                $ventaLinea = $ventaLineas[$linea['venta_linea_id']];
                $valorUnitario = $ventaLinea->valorUnitarioPagado();

                $devolucion->lineas()->create([
                    'venta_linea_id' => $ventaLinea->id,
                    'cantidad' => $linea['cantidad'],
                    'reintegra_inventario' => $linea['reintegra_inventario'],
                    'valor_unitario' => $valorUnitario,
                ]);

                if (! $validada) {
                    continue;
                }

                if ($linea['reintegra_inventario']) {
                    $this->movimientoStock->reintegrar(
                        $variantes[$ventaLinea->variante_id],
                        $linea['cantidad'],
                        TipoMovimiento::Devolucion,
                        $devolucion,
                        $usuario,
                    );
                }

                $saldoGenerado = bcadd($saldoGenerado, bcmul($valorUnitario, (string) $linea['cantidad'], 2), 2);
            }

            if ($validada && bccomp($saldoGenerado, '0', 2) > 0) {
                $devolucion->saldo_generado = $saldoGenerado;
                $devolucion->save();

                $cliente = Cliente::whereKey($venta->cliente_id)->lockForUpdate()->firstOrFail();
                $this->movimientoSaldoFavor->generar($cliente, $saldoGenerado, $devolucion);
            }

            return $devolucion->load('lineas');
        });
    }

    /**
     * @param  list<array{venta_linea_id: int, cantidad: int, reintegra_inventario: bool}>  $lineas
     * @param  Collection<int, VentaLinea>  $ventaLineas
     */
    private function validarCantidades(Venta $venta, array $lineas, $ventaLineas): void
    {
        foreach ($lineas as $linea) {
            $ventaLinea = $ventaLineas->get($linea['venta_linea_id']);

            if ($ventaLinea === null) {
                throw DevolucionInvalidaException::lineaAjena($venta);
            }

            $disponible = $ventaLinea->cantidad - $ventaLinea->cantidadDevuelta();

            if ($linea['cantidad'] > $disponible) {
                throw DevolucionInvalidaException::excedeCantidad($ventaLinea, $linea['cantidad'], $disponible);
            }
        }
    }
}
