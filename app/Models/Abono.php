<?php

namespace App\Models;

use Database\Factories\AbonoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Abono a una venta a crédito (RF-014; bloque C2).
 *
 * Modelo "una deuda por venta": cada abono baja
 * `ventas.credito_saldo_pendiente`. Lo registra el servicio RegistrarAbono
 * dentro de una transacción con la venta bloqueada y con la guarda de no
 * sobrepago.
 *
 * Es un registro de auditoría: no se edita ni se borra.
 */
#[Fillable(['venta_id', 'monto', 'fecha', 'usuario_id'])]
class Abono extends Model
{
    /** @use HasFactory<AbonoFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'abonos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
