<?php

namespace App\Services\Ventas;

use App\Enums\EstadoVenta;
use App\Enums\TipoMovimiento;
use App\Exceptions\VentaNoAnulableException;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Support\Facades\DB;

/**
 * Anulación de una venta antes de la entrega (RF-010; flujo 4.3).
 *
 * En una transacción (bloque B1):
 *
 * 1. Recargar la venta con `lockForUpdate` y revalidar que sigue siendo
 *    anulable (confirmada y no entregada) — la Policy ya hizo la comprobación
 *    amable de permiso; esto protege contra un cambio de estado entre medias.
 * 2. Bloquear las variantes de la venta, ordenadas por `id`.
 * 3. Reintegrar el stock de cada línea + movimiento `tipo=anulacion`.
 * 4. Marcar la venta como anulada con su auditoría.
 *
 * Sprint 3: la venta es siempre de contado, así que no hay saldo a favor ni
 * deuda de crédito que revertir (esos ramales del flujo 4.3 entran en Sprint 4).
 */
class AnularVenta
{
    public function __construct(private readonly MovimientoStock $movimientoStock) {}

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

            $venta->estado = EstadoVenta::Anulada;
            $venta->anulada_at = now();
            $venta->anulada_por = $usuario->id;
            $venta->motivo_anulacion = $motivo;
            $venta->save();

            return $venta;
        });
    }
}
