<?php

namespace App\Models;

use App\Observers\ProductoObserver;
use Database\Factories\ProductoHistorialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una entrada del historial de un producto (RF-016, bloque B3a).
 *
 * La escribe {@see ProductoObserver} — una fila por campo
 * modificado, más una fila `campo = 'alta'` al crear el producto y una
 * `campo = 'estado'` al desactivarlo o reactivarlo. Solo lectura desde la
 * aplicación: nunca se edita ni se borra.
 */
#[Fillable(['producto_id', 'usuario_id', 'campo', 'valor_anterior', 'valor_nuevo'])]
class ProductoHistorial extends Model
{
    /** @use HasFactory<ProductoHistorialFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'producto_historial';

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Usuario que hizo el cambio; NULL si ocurrió fuera de una sesión
     * (seeders, consola, tests).
     *
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
