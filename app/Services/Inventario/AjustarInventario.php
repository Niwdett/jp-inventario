<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Models\AjusteInventario;
use App\Models\Variante;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ajuste manual de inventario (RF-006, RN-10: el conteo físico prevalece).
 *
 * Transacción con la variante bloqueada (bloque B1): fija `stock` al valor
 * contado, registra el ajuste y anexa el movimiento con el delta. El movimiento
 * no lleva `usuario_id` (RN-15). El `costo_promedio` no cambia: un ajuste
 * modifica cantidad, no costo.
 */
class AjustarInventario
{
    public function ejecutar(Variante $variante, int $cantidadNueva, ?string $motivo): AjusteInventario
    {
        return DB::transaction(function () use ($variante, $cantidadNueva, $motivo) {
            /** @var Variante $variante */
            $variante = Variante::whereKey($variante->getKey())->lockForUpdate()->firstOrFail();

            $cantidadAnterior = $variante->stock;
            $delta = $cantidadNueva - $cantidadAnterior;

            // Un ajuste sin cambio de cantidad no tiene sentido y ensuciaría el
            // ledger con un movimiento de delta 0. El Form Request ya lo bloquea
            // en el flujo HTTP; esto protege cualquier otra llamada al servicio.
            if ($delta === 0) {
                throw new InvalidArgumentException('La cantidad contada es igual al stock actual: no hay nada que ajustar.');
            }

            // Asignación directa: `stock` no es fillable.
            $variante->stock = $cantidadNueva;
            $variante->save();

            $ajuste = AjusteInventario::create([
                'variante_id' => $variante->id,
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_nueva' => $cantidadNueva,
                'motivo' => $motivo,
            ]);

            $ajuste->movimientos()->create([
                'variante_id' => $variante->id,
                'tipo' => TipoMovimiento::Ajuste,
                'cantidad' => $delta,
                'stock_resultante' => $cantidadNueva,
                'usuario_id' => null,
            ]);

            return $ajuste;
        });
    }
}
