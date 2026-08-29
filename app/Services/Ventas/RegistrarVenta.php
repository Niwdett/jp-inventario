<?php

namespace App\Services\Ventas;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registro y confirmación de una venta (RF-008, RF-009; flujo 4.2).
 *
 * Todo ocurre en una transacción (bloque B1):
 *
 * 1. Bloquear las variantes involucradas, ordenadas por `id` (evita deadlocks).
 * 2. Validar `stock >= cantidad` en cada línea.
 * 3. Generar el `numero` correlativo (bloquea la secuencia, S1).
 * 4. Insertar la venta y sus líneas con el snapshot del costo (A2).
 * 5. Descontar stock con guarda de no-negatividad + movimiento `tipo=venta`.
 * 6. Fijar los totales (aritmética decimal, nunca float — E2).
 *
 * Sprint 3: solo ventas de contado. `$cliente` se acepta pero no se usa; el
 * crédito y el saldo a favor entran en el Sprint 4.
 */
class RegistrarVenta
{
    public function __construct(private readonly MovimientoStock $movimientoStock) {}

    /**
     * @param  list<array{variante_id: int, cantidad: int, precio_unitario: string, descuento_porcentaje: string|null}>  $lineas
     *
     * @throws StockInsuficienteException
     */
    public function ejecutar(array $lineas, MetodoPago $metodoPago, ?Cliente $cliente, User $usuario): Venta
    {
        return DB::transaction(function () use ($lineas, $metodoPago, $cliente, $usuario) {
            $ids = collect($lineas)->pluck('variante_id')->unique()->sort()->values();

            /** @var Collection<int, Variante> $variantes */
            $variantes = Variante::query()
                ->whereIn('id', $ids)
                ->whereHas('producto')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variantes->count() !== $ids->count()) {
                throw new \RuntimeException('Alguna de las variantes seleccionadas ya no está disponible.');
            }

            foreach ($lineas as $linea) {
                $variante = $variantes[$linea['variante_id']];

                if ($variante->stock < $linea['cantidad']) {
                    throw StockInsuficienteException::paraVariante($variante, $linea['cantidad'], $variante->stock);
                }
            }

            $venta = new Venta;
            $venta->numero = Venta::generarNumero();
            $venta->cliente_id = $cliente?->id;
            $venta->usuario_id = $usuario->id;
            $venta->fecha_venta = now();
            $venta->metodo_pago = $metodoPago;
            $venta->estado = EstadoVenta::Confirmada;
            $venta->subtotal = '0';
            $venta->descuento_total = '0';
            $venta->total = '0';
            $venta->save();

            $subtotal = '0';
            $total = '0';

            foreach ($lineas as $linea) {
                $variante = $variantes[$linea['variante_id']];
                $cantidad = $linea['cantidad'];
                $precio = $linea['precio_unitario'];
                $descuento = $linea['descuento_porcentaje'];

                $importeLinea = $this->importeLinea($precio, $cantidad, $descuento);

                $venta->lineas()->create([
                    'variante_id' => $variante->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento_porcentaje' => $descuento,
                    'costo_unitario_snapshot' => (string) $variante->costo_promedio,
                    'importe_linea' => $importeLinea,
                ]);

                $this->movimientoStock->descontar($variante, $cantidad, TipoMovimiento::Venta, $venta, $usuario);

                $subtotal = bcadd($subtotal, bcmul($precio, (string) $cantidad, 2), 2);
                $total = bcadd($total, $importeLinea, 2);
            }

            $venta->subtotal = $subtotal;
            $venta->total = $total;
            $venta->descuento_total = bcsub($subtotal, $total, 2);
            $venta->save();

            return $venta->load('lineas');
        });
    }

    /**
     * importe_linea = round( precio_unitario * cantidad * (1 - descuento%/100) , 2 )
     * con aritmética decimal (E2).
     */
    private function importeLinea(string $precio, int $cantidad, ?string $descuentoPorcentaje): string
    {
        $bruto = bcmul($precio, (string) $cantidad, 6);
        $factor = bcsub('1', bcdiv($descuentoPorcentaje ?? '0', '100', 6), 6);

        return bcround(bcmul($bruto, $factor, 6), 2);
    }
}
