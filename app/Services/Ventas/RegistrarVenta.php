<?php

namespace App\Services\Ventas;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\ClienteEnMoraException;
use App\Exceptions\PagoVentaInvalidoException;
use App\Exceptions\SaldoFavorInsuficienteException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Clientes\MovimientoSaldoFavor;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registro y confirmación de una venta (RF-008, RF-009, RF-012, RF-015; flujo 4.2).
 *
 * Todo ocurre en una transacción (bloque B1). Orden de bloqueo: primero las
 * variantes (por `id`), luego el cliente — el mismo orden en cada operación,
 * para evitar deadlocks.
 *
 * 1. Bloquear variantes, validar `stock >= cantidad`.
 * 2. Bloquear el cliente si la venta lo necesita (crédito o saldo a favor).
 * 3. Calcular totales (aritmética decimal, nunca float — E2).
 * 4. Resolver el pago: aplicar saldo a favor (C1), calcular el restante y, si va
 *    a crédito, comprobar la mora (RN-09).
 * 5. Insertar la venta y sus líneas con el snapshot del costo (A2).
 * 6. Descontar stock con guarda de no-negatividad + movimiento `tipo=venta`.
 *
 * Modelo de pago (C1): `total = saldo_favor_aplicado + restante`, y el
 * `restante` se cubre con UN `metodo_pago`. Si el saldo a favor cubre el 100 %
 * (`restante = 0`) la venta se registra como `efectivo` sin deuda.
 */
class RegistrarVenta
{
    public function __construct(
        private readonly MovimientoStock $movimientoStock,
        private readonly MovimientoSaldoFavor $movimientoSaldoFavor,
    ) {}

    /**
     * @param  list<array{variante_id: int, cantidad: int, precio_unitario: string, descuento_porcentaje: string|null}>  $lineas
     *
     * @throws StockInsuficienteException
     * @throws SaldoFavorInsuficienteException
     * @throws ClienteEnMoraException
     * @throws PagoVentaInvalidoException
     */
    public function ejecutar(
        array $lineas,
        MetodoPago $metodoPago,
        ?Cliente $cliente,
        User $usuario,
        string $saldoFavorAplicado = '0',
        bool $autorizarMora = false,
    ): Venta {
        return DB::transaction(function () use ($lineas, $metodoPago, $cliente, $usuario, $saldoFavorAplicado, $autorizarMora) {
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

            $usaCliente = $metodoPago === MetodoPago::Credito || bccomp($saldoFavorAplicado, '0', 2) > 0;

            if ($usaCliente && $cliente === null) {
                throw PagoVentaInvalidoException::clienteRequerido();
            }

            $clienteBloqueado = $cliente !== null
                ? Cliente::whereKey($cliente->id)->lockForUpdate()->firstOrFail()
                : null;

            [$subtotal, $total, $importes] = $this->calcularTotales($lineas);

            if (bccomp($saldoFavorAplicado, $total, 2) > 0) {
                throw PagoVentaInvalidoException::saldoExcedeTotal($saldoFavorAplicado, $total);
            }

            $restante = bcsub($total, $saldoFavorAplicado, 2);
            $esCredito = $metodoPago === MetodoPago::Credito && bccomp($restante, '0', 2) > 0;
            $metodoFinal = bccomp($restante, '0', 2) === 0 ? MetodoPago::Efectivo : $metodoPago;

            $venta = new Venta;
            $venta->numero = Venta::generarNumero();
            $venta->cliente_id = $clienteBloqueado?->id;
            $venta->usuario_id = $usuario->id;
            $venta->fecha_venta = now();
            $venta->metodo_pago = $metodoFinal;
            $venta->estado = EstadoVenta::Confirmada;
            $venta->subtotal = $subtotal;
            $venta->descuento_total = bcsub($subtotal, $total, 2);
            $venta->total = $total;
            $venta->saldo_favor_aplicado = $saldoFavorAplicado;

            if ($esCredito) {
                $this->aplicarReglaDeMora($venta, $clienteBloqueado, $usuario, $autorizarMora);
                $venta->credito_monto = $restante;
                $venta->credito_saldo_pendiente = $restante;
            }

            $venta->save();

            foreach ($lineas as $indice => $linea) {
                $variante = $variantes[$linea['variante_id']];

                $venta->lineas()->create([
                    'variante_id' => $variante->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'descuento_porcentaje' => $linea['descuento_porcentaje'],
                    'costo_unitario_snapshot' => (string) $variante->costo_promedio,
                    'importe_linea' => $importes[$indice],
                ]);

                $this->movimientoStock->descontar($variante, $linea['cantidad'], TipoMovimiento::Venta, $venta, $usuario);
            }

            if (bccomp($saldoFavorAplicado, '0', 2) > 0) {
                $this->movimientoSaldoFavor->aplicar($clienteBloqueado, $saldoFavorAplicado, $venta);
            }

            return $venta->load('lineas');
        });
    }

    /**
     * RN-09: un cliente en mora no puede recibir una nueva venta a crédito. El
     * Empleado nunca la fuerza; el Administrador sí, si la autoriza
     * explícitamente (se registra en `credito_autorizado_por`).
     */
    private function aplicarReglaDeMora(Venta $venta, Cliente $cliente, User $usuario, bool $autorizarMora): void
    {
        if (! $cliente->estaEnMora()) {
            return;
        }

        if (! $usuario->esAdministrador()) {
            throw ClienteEnMoraException::empleado($cliente);
        }

        if (! $autorizarMora) {
            throw ClienteEnMoraException::requiereAutorizacion($cliente);
        }

        $venta->credito_autorizado_por = $usuario->id;
    }

    /**
     * Devuelve `[subtotal, total, importes_por_linea]` con aritmética decimal.
     *
     * - `subtotal` = Σ(precio · cantidad)
     * - `total` = Σ(importe_linea) tras el descuento por línea
     *
     * @param  list<array{cantidad: int, precio_unitario: string, descuento_porcentaje: string|null}>  $lineas
     * @return array{0: string, 1: string, 2: list<string>}
     */
    private function calcularTotales(array $lineas): array
    {
        $subtotal = '0';
        $total = '0';
        $importes = [];

        foreach ($lineas as $linea) {
            $importe = $this->importeLinea($linea['precio_unitario'], $linea['cantidad'], $linea['descuento_porcentaje']);
            $importes[] = $importe;

            $subtotal = bcadd($subtotal, bcmul($linea['precio_unitario'], (string) $linea['cantidad'], 2), 2);
            $total = bcadd($total, $importe, 2);
        }

        return [$subtotal, $total, $importes];
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
