<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Entrada de mercancía (RF-005). Registro histórico e inmutable de una compra;
 * al crearse recalcula `variantes.costo_promedio` (promedio ponderado móvil, A1).
 *
 * Una entrada mal capturada no se edita ni se borra: se **anula** (decisión A4).
 * `cantidad` y `costo_unitario` siguen siendo la auditoría del recálculo; la
 * anulación se marca con `anulada_at`/`anulada_por`/`motivo_anulacion` y
 * `variantes.costo_promedio`/`stock` se reconstruyen reproduciendo el ledger.
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
            'anulada_at' => 'datetime',
        ];
    }

    /**
     * ¿La entrada puede anularse todavía? (guarda A4.a: no hay doble anulación).
     */
    public function esAnulable(): bool
    {
        return $this->anulada_at === null;
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
     * Administrador que anuló la entrada (decisión A4). NULL si sigue vigente.
     *
     * @return BelongsTo<User, $this>
     */
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    /**
     * @return MorphMany<MovimientoInventario, $this>
     */
    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }
}
