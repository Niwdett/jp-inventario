<?php

namespace App\Models;

use Database\Factories\VarianteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Variante de producto por talla y color (RF-004, A3).
 *
 * `stock` y `costo_promedio` no se editan a mano desde el CRUD de variantes:
 * cambian por entradas de mercancía (A1), ventas y ajustes de inventario, cada
 * uno dentro de su transacción y con su registro en `movimientos_inventario`.
 */
#[Fillable(['producto_id', 'talla', 'color', 'codigo'])]
class Variante extends Model
{
    /** @use HasFactory<VarianteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'variantes';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'costo_promedio' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * @return HasMany<EntradaInventario, $this>
     */
    public function entradas(): HasMany
    {
        return $this->hasMany(EntradaInventario::class);
    }

    /**
     * @return HasMany<AjusteInventario, $this>
     */
    public function ajustes(): HasMany
    {
        return $this->hasMany(AjusteInventario::class);
    }

    /**
     * Libro de movimientos de esta variante (bloque B3b). El stock de la variante
     * debe coincidir con el `stock_resultante` del movimiento más reciente.
     *
     * @return HasMany<MovimientoInventario, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * Renglones de venta que han vendido esta variante. Lo usan la anulación
     * (para reintegrar stock) y la verificación del invariante del ledger.
     *
     * @return HasMany<VentaLinea, $this>
     */
    public function ventaLineas(): HasMany
    {
        return $this->hasMany(VentaLinea::class);
    }

    /**
     * Etiqueta legible: "38 / Negro" o "Única / Única".
     */
    public function etiqueta(): string
    {
        return "{$this->talla} / {$this->color}";
    }

    /**
     * Opciones para los selects de inventario: variantes activas de productos
     * activos, `[id => "Producto (CÓDIGO) — talla / color"]`, ordenadas por producto.
     *
     * @return Collection<int, string>
     */
    public static function opcionesParaSelect(): Collection
    {
        return static::query()
            ->with('producto:id,nombre,codigo_interno')
            ->whereHas('producto')
            ->get()
            ->sortBy(fn (self $v) => mb_strtolower($v->producto->nombre).$v->talla.$v->color)
            ->mapWithKeys(fn (self $v) => [
                $v->id => "{$v->producto->nombre} ({$v->producto->codigo_interno}) — {$v->etiqueta()}",
            ]);
    }

    /**
     * Precio de referencia (sugerido) de cada variante activa, `[id => "45000.00"]`,
     * para pre-llenar el precio al agregar una línea de venta. Es solo una
     * sugerencia: el vendedor puede cambiarlo (descuentos, ajustes).
     *
     * @return Collection<int, string>
     */
    public static function preciosReferenciaPorId(): Collection
    {
        return static::query()
            ->join('productos', 'productos.id', '=', 'variantes.producto_id')
            ->whereNull('productos.deleted_at')
            ->pluck('productos.precio_referencia', 'variantes.id')
            ->map(fn ($precio) => (string) $precio);
    }

    /**
     * ¿Esta variante está en o por debajo del umbral de stock bajo de su producto? (RF-007)
     */
    public function estaEnStockBajo(): bool
    {
        return $this->stock <= $this->producto->umbral_stock_bajo;
    }

    /**
     * Variantes activas cuyo stock está en o por debajo del umbral de su
     * producto activo (RF-007, RN-14). Devuelve columnas de `variantes`.
     */
    #[Scope]
    protected function stockBajo(Builder $query): void
    {
        $query->select('variantes.*')
            ->join('productos', 'productos.id', '=', 'variantes.producto_id')
            ->whereNull('productos.deleted_at')
            ->whereColumn('variantes.stock', '<=', 'productos.umbral_stock_bajo');
    }
}
