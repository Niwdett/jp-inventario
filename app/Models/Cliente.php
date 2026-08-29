<?php

namespace App\Models;

use App\Enums\MetodoPago;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cliente del negocio (RF-013).
 *
 * `saldo_favor` es un valor cacheado (C1): siempre igual a la suma de
 * `saldo_favor_movimientos.monto` del cliente. Solo lo mueven los servicios de
 * saldo a favor (devolución que genera, venta que aplica) dentro de una
 * transacción con la fila del cliente bloqueada; nunca es fillable.
 */
#[Fillable(['nombre', 'telefono', 'cedula'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    /**
     * Días desde `ventas.fecha_venta` a partir de los cuales una venta a crédito
     * con saldo pendiente pone al cliente en mora (RN-09 / C2). Cuando el
     * negocio defina un plazo formal se añadirá `fecha_vencimiento` y esta
     * constante dejará de usarse.
     */
    public const DIAS_MORA = 15;

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
     * No se puede eliminar (soft-delete) un cliente con crédito pendiente o con
     * saldo a favor sin consumir: su historia de dinero debe seguir accesible
     * (punto 6 de las decisiones del Sprint 4). El controlador comprueba primero
     * para dar un mensaje amable; esto es la red de seguridad para cualquier
     * otro camino (tinker, seeders, código futuro).
     */
    protected static function booted(): void
    {
        static::deleting(fn (Cliente $cliente): bool => $cliente->puedeEliminarse());
    }

    /**
     * ¿El cliente no tiene dinero pendiente en ninguna dirección?
     */
    public function puedeEliminarse(): bool
    {
        return (float) $this->saldo_favor <= 0 && $this->ventasACredito()->doesntExist();
    }

    /**
     * @return HasMany<Venta, $this>
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Libro de saldo a favor del cliente (C1).
     *
     * @return HasMany<SaldoFavorMovimiento, $this>
     */
    public function saldoFavorMovimientos(): HasMany
    {
        return $this->hasMany(SaldoFavorMovimiento::class);
    }

    /**
     * Ventas a crédito con deuda pendiente (base de la mora, RN-09).
     *
     * @return HasMany<Venta, $this>
     */
    public function ventasACredito(): HasMany
    {
        return $this->ventas()
            ->where('metodo_pago', MetodoPago::Credito)
            ->where('credito_saldo_pendiente', '>', 0);
    }

    /**
     * ¿El cliente está en mora? (RN-09): tiene alguna venta a crédito con saldo
     * pendiente cuya `fecha_venta` es anterior a hoy − {@see self::DIAS_MORA}.
     *
     * El Empleado nunca puede vender a crédito a un cliente en mora; el
     * Administrador puede autorizarlo (se registra en
     * `ventas.credito_autorizado_por`).
     */
    public function estaEnMora(): bool
    {
        return $this->ventasACredito()
            ->where('fecha_venta', '<', now()->subDays(self::DIAS_MORA))
            ->exists();
    }
}
