<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Categoría de producto (A3.2). El `prefijo_codigo` alimenta el código interno
 * autogenerado de sus productos (p. ej. prefijo "CAL" → "CAL-0001").
 *
 * Soft-delete siempre; además, una categoría no puede eliminarse mientras
 * tenga productos activos (regla del Bloque B2, aplicada en el controlador).
 */
#[Fillable(['nombre', 'prefijo_codigo'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'categorias';

    /**
     * Regla B2: una categoría con productos activos no puede eliminarse.
     * Devolver `false` desde `deleting` cancela el borrado. El controlador hace
     * la comprobación primero para dar un mensaje amable; esto es la red de
     * seguridad para cualquier otro camino (tinker, seeders, código futuro).
     */
    protected static function booted(): void
    {
        static::deleting(fn (Categoria $categoria): bool => $categoria->productos()->doesntExist());
    }

    /**
     * @return HasMany<Producto, $this>
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
