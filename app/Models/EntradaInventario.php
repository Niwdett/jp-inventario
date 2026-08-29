<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Entrada de mercancía (RF-005). Registro histórico e inmutable de una compra;
 * al crearse recalcula `variantes.costo_promedio` (promedio ponderado móvil, A1).
 */
#[Fillable(['variante_id', 'usuario_id', 'cantidad', 'costo_unitario', 'fecha', 'proveedor'])]
class EntradaInventario extends Model
{
    protected $table = 'entradas_inventario';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'costo_unitario' => 'decimal:4',
            'fecha' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return MorphMany<MovimientoInventario, $this>
     */
    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }
}
