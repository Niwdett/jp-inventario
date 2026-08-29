<?php

namespace App\Models;

use App\Enums\TipoSaldoFavor;
use Database\Factories\SaldoFavorMovimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Renglón del libro de saldo a favor (bloque C1). Cada devolución validada
 * (tipo `generado`) o cada venta que aplica saldo (tipo `aplicado`) inserta uno
 * dentro de su transacción, con la fila del cliente bloqueada.
 *
 * Es un registro de auditoría: no se edita ni se borra. `clientes.saldo_favor`
 * es la lectura rápida y siempre coincide con la suma de `monto` de este libro.
 */
#[Fillable(['cliente_id', 'tipo', 'monto', 'referencia_type', 'referencia_id'])]
class SaldoFavorMovimiento extends Model
{
    /** @use HasFactory<SaldoFavorMovimientoFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'saldo_favor_movimientos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoSaldoFavor::class,
            'monto' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }
}
