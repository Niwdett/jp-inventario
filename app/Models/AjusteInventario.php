<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ajuste manual de inventario (RF-006, RN-10). Sin `usuario_id` (RN-15).
 * Registra el stock antes y después; el delta genera un movimiento de inventario.
 */
#[Fillable(['variante_id', 'cantidad_anterior', 'cantidad_nueva', 'motivo'])]
class AjusteInventario extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ajustes_inventario';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_anterior' => 'integer',
            'cantidad_nueva' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Variante, $this>
     */
    public function variante(): BelongsTo
    {
        return $this->belongsTo(Variante::class);
    }

    /**
     * @return MorphMany<MovimientoInventario, $this>
     */
    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }
}
