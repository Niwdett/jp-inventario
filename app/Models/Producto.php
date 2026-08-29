<?php

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Producto del catálogo (RF-003, A3).
 *
 * Un producto siempre tiene al menos una variante (A3): el alta crea la primera
 * y no se puede borrar la última. El `codigo_interno` se autogenera a partir del
 * prefijo de la categoría y no se edita después; la categoría tampoco cambia
 * tras el alta (el código quedaría inconsistente con el prefijo).
 */
#[Fillable(['categoria_id', 'nombre', 'marca', 'codigo_interno', 'precio_referencia', 'foto', 'umbral_stock_bajo', 'proveedor'])]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'productos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_referencia' => 'decimal:2',
            'umbral_stock_bajo' => 'integer',
        ];
    }

    /**
     * Al eliminar un producto se arrastran sus variantes activas al mismo estado
     * (una variante de un producto eliminado no debe poder venderse). Al
     * restaurarlo se devuelven **solo** las que cayeron con él: se identifican
     * por haber sido eliminadas en la misma operación (mismo instante, con un
     * pequeño margen). Una variante que el Administrador había borrado a mano
     * antes se queda borrada.
     */
    protected static function booted(): void
    {
        static::deleting(function (Producto $producto): void {
            if (! $producto->isForceDeleting()) {
                $producto->variantes()->get()->each->delete();
            }
        });

        static::restoring(function (Producto $producto): void {
            $limite = $producto->deleted_at?->copy()->subSeconds(10);

            $producto->variantes()
                ->onlyTrashed()
                ->when($limite, fn ($query) => $query->where('deleted_at', '>=', $limite))
                ->get()
                ->each
                ->restore();
        });
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return HasMany<Variante, $this>
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(Variante::class);
    }

    /**
     * Siguiente código interno disponible para la categoría dada: `PREFIJO-NNNN`,
     * correlativo por categoría contando también los productos eliminados (para
     * no reutilizar un número ya emitido).
     *
     * El llamador debe haber bloqueado la fila de la categoría
     * (`lockForUpdate`) dentro de una transacción: así dos altas simultáneas de
     * la misma categoría se serializan y no calculan el mismo correlativo.
     */
    public static function generarCodigoInterno(Categoria $categoria): string
    {
        $correlativo = static::withTrashed()
            ->where('categoria_id', $categoria->id)
            ->count() + 1;

        return sprintf('%s-%04d', $categoria->prefijo_codigo, $correlativo);
    }
}
