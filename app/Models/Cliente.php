<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cliente del negocio (RF-013).
 *
 * En el Sprint 3 el modelo es **inerte**: una venta de contado puede referenciar
 * un cliente pero el sistema no lo exige ni ofrece todavía su gestión. El CRUD
 * de clientes, el crédito, los abonos, la mora y el saldo a favor entran en el
 * Sprint 4. `saldo_favor` es un valor cacheado (C1) que aún no mueve nada.
 */
#[Fillable(['nombre', 'telefono', 'cedula'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saldo_favor' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Venta, $this>
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
