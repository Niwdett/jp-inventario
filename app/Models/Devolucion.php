<?php

namespace App\Models;

use App\Enums\EstadoDevolucion;
use Database\Factories\DevolucionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Devolución de mercancía ya entregada (RF-011; flujo 4.4).
 *
 * La valida el Administrador. Si es `validada` genera saldo a favor (nunca
 * efectivo, RN-11) y, por línea, pudo reintegrar stock (RN-13). Si es
 * `rechazada` se registra para auditoría sin efectos. La escribe el servicio
 * RegistrarDevolucion en una transacción con el cliente bloqueado.
 *
 * No se edita ni se anula una vez creada (fuera de alcance del MVP).
 */
#[Fillable(['venta_id', 'usuario_id', 'fecha', 'estado', 'motivo', 'saldo_generado'])]
class Devolucion extends Model
{
    /** @use HasFactory<DevolucionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'devoluciones';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoDevolucion::class,
            'saldo_generado' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Venta, $this>
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Administrador que validó la devolución.
     *
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return HasMany<DevolucionLinea, $this>
     */
    public function lineas(): HasMany
    {
        return $this->hasMany(DevolucionLinea::class);
    }

    /**
     * Movimientos de inventario originados por esta devolución (tipo
     * `devolucion`, uno por línea con `reintegra_inventario = true`).
     *
     * @return MorphMany<MovimientoInventario, $this>
     */
    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }

    /**
     * Movimiento del libro de saldo a favor generado por esta devolución.
     *
     * @return MorphMany<SaldoFavorMovimiento, $this>
     */
    public function saldoFavorMovimientos(): MorphMany
    {
        return $this->morphMany(SaldoFavorMovimiento::class, 'referencia');
    }
}
