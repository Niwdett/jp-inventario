<?php

namespace App\Services\Ventas;

use App\Enums\EstadoVenta;
use App\Enums\TipoMovimiento;
use App\Exceptions\VentaNoAnulableException;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Clientes\MovimientoSaldoFavor;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Support\Facades\DB;

/**
 * Anulación de una venta antes de la entrega (RF-010; flujo 4.3).
 *
 * En una transacción (bloque B1), con el mismo orden de bloqueo que
 * {@see RegistrarVenta} (variantes por `id`, luego el cliente):
 *
 * 1. Recargar la venta con `lockForUpdate` y revalidar que sigue siendo
 *    anulable (confirmada y no entregada).
 * 2. Bloquear las variantes de la venta y reintegrar su stock + movimiento
 *    `tipo=anulacion`.
 * 3. Si la venta aplicó saldo a favor, devolverlo (`tipo=generado`).
 * 4. Si fue a crédito, anular la deuda (`credito_saldo_pendiente = 0`); los
 *    abonos ya registrados se convierten en saldo a favor — nunca efectivo
 *    (RN-11).
 * 5. Marcar la venta como anulada con su auditoría.
 */
class AnularVenta
{
    public function __construct(
        private readonly MovimientoStock $movimientoStock,
        private readonly MovimientoSaldoFavor $movimientoSaldoFavor,
    ) {}

    /**
     * @throws VentaNoAnulableException
     */
    public function ejecutar(Venta $venta, string $motivo, User $usuario): Venta
    {
        return DB::transaction(function () use ($venta, $motivo, $usuario) {
            /** @var Venta $venta */
            $venta = Venta::whereKey($venta->getKey())->lockForUpdate()->firstOrFail();

            if (! $venta->esAnulable()) {
                throw VentaNoAnulableException::para($venta);
            }

            $lineas = $venta->lineas()->get();

            $variantes = Variante::query()
                ->whereIn('id', $lineas->pluck('variante_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lineas as $linea) {
                $this->movimientoStock->reintegrar(
                    $variantes[$linea->variante_id],
                    $linea->cantidad,
                    TipoMovimiento::Anulacion,
                    $venta,
                    $usuario,
                );
            }

            $this->revertirDineroDelCliente($venta);

            $venta->estado = EstadoVenta::Anulada;
            $venta->anulada_at = now();
            $venta->anulada_por = $usuario->id;
            $venta->motivo_anulacion = $motivo;
            $venta->save();

            return $venta;
        });
    }

    /**
     * Devuelve al cliente el saldo a favor que la venta consumió y el importe
     * de los abonos hechos contra su crédito, y cancela la deuda pendiente.
     * Todo como saldo a favor (RN-11), con el cliente bloqueado.
     */
    private function revertirDineroDelCliente(Venta $venta): void
    {
        $saldoAplicado = (string) $venta->saldo_favor_aplicado;
        $abonado = $venta->esCredito() ? (string) $venta->abonos()->sum('monto') : '0';
        $aDevolver = bcadd($saldoAplicado, $abonado, 2);

        if (bccomp($aDevolver, '0', 2) > 0) {
            $cliente = Cliente::whereKey($venta->cliente_id)->lockForUpdate()->firstOrFail();
            $this->movimientoSaldoFavor->generar($cliente, $aDevolver, $venta);
        }

        if ($venta->esCredito()) {
            $venta->credito_saldo_pendiente = '0';
        }
    }
}
