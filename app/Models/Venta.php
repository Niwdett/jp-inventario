<?php

namespace App\Models;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Services\Ventas\AnularVenta;
use App\Services\Ventas\RegistrarVenta;
use Database\Factories\VentaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * Venta (RF-008, RF-009, RF-010).
 *
 * Una venta **no se elimina nunca** (B2): la anulación es un cambio de estado.
 * El registro (transacción + bloqueo de variantes + descuento de stock con
 * guarda de no-negatividad) vive en {@see RegistrarVenta};
 * la anulación en {@see AnularVenta}.
 *
 * Los campos calculados (`numero`, totales, `estado`, `entregada_at`,
 * `anulada_*`, crédito) no son fillable: los fijan los servicios por asignación
 * directa, igual que `variantes.stock`.
 */
#[Fillable(['cliente_id', 'usuario_id', 'fecha_venta', 'metodo_pago'])]
class Venta extends Model
{
    /** @use HasFactory<VentaFactory> */
    use HasFactory;

    protected $table = 'ventas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_venta' => 'datetime',
            'entregada_at' => 'datetime',
            'anulada_at' => 'datetime',
            'metodo_pago' => MetodoPago::class,
            'estado' => EstadoVenta::class,
            'subtotal' => 'decimal:2',
            'descuento_total' => 'decimal:2',
            'total' => 'decimal:2',
            'saldo_favor_aplicado' => 'decimal:2',
            'credito_monto' => 'decimal:2',
            'credito_saldo_pendiente' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<VentaLinea, $this>
     */
    public function lineas(): HasMany
    {
        return $this->hasMany(VentaLinea::class);
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Vendedor que registró la venta (RN-08).
     *
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    /**
     * Administrador que autorizó la venta a crédito pese a la mora del cliente
     * (RN-09; solo se llena en ese caso).
     *
     * @return BelongsTo<User, $this>
     */
    public function creditoAutorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credito_autorizado_por');
    }

    /**
     * Abonos registrados contra la deuda de esta venta (RF-014).
     *
     * @return HasMany<Abono, $this>
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }

    /**
     * Devoluciones originadas por esta venta (RF-011).
     *
     * @return HasMany<Devolucion, $this>
     */
    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class);
    }

    /**
     * Movimientos del libro de saldo a favor ligados a esta venta: el saldo
     * aplicado como pago (tipo `aplicado`) y, si se anula, su reverso
     * (tipo `generado`).
     *
     * @return MorphMany<SaldoFavorMovimiento, $this>
     */
    public function saldoFavorMovimientos(): MorphMany
    {
        return $this->morphMany(SaldoFavorMovimiento::class, 'referencia');
    }

    /**
     * ¿Es una venta a crédito? (independientemente de si ya está saldada).
     */
    public function esCredito(): bool
    {
        return $this->metodo_pago === MetodoPago::Credito;
    }

    /**
     * Solo ventas confirmadas: las anuladas no cuentan para reportes ni para el
     * dashboard (RF-017, RF-019, RF-020).
     */
    #[Scope]
    protected function confirmadas(Builder $query): void
    {
        $query->where('estado', EstadoVenta::Confirmada);
    }

    /**
     * Ganancia bruta de la venta (RN-04): Σ de la ganancia de cada línea, sin
     * descontar todavía las devoluciones. Requiere `lineas` cargada. Aritmética
     * decimal (E2).
     */
    public function gananciaBruta(): string
    {
        return $this->lineas->reduce(
            fn (string $acumulado, VentaLinea $linea) => bcadd($acumulado, $linea->ganancia(), 2),
            '0',
        );
    }

    /**
     * Movimientos de inventario originados por esta venta (venta y, si se anula,
     * anulacion).
     *
     * @return MorphMany<MovimientoInventario, $this>
     */
    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoInventario::class, 'referencia');
    }

    /**
     * ¿Se puede anular? Solo si sigue confirmada y aún no se ha entregado
     * (RF-010; tras la entrega el camino es la devolución). La comprobación de
     * permiso (propia / cualquiera) vive en la Policy.
     */
    public function esAnulable(): bool
    {
        return $this->estado === EstadoVenta::Confirmada && $this->entregada_at === null;
    }

    /**
     * ¿Se puede marcar como entregada? Solo una venta confirmada y no entregada.
     */
    public function puedeEntregarse(): bool
    {
        return $this->estado === EstadoVenta::Confirmada && $this->entregada_at === null;
    }

    /**
     * Siguiente número de venta: correlativo global `V-000001`, único y sin
     * reutilización (sub-decisión S1).
     *
     * El llamador debe estar dentro de una transacción: la fila de la secuencia
     * se bloquea (`lockForUpdate`) mientras se lee y se incrementa, de modo que
     * dos ventas simultáneas se serializan y un `ROLLBACK` deshace también el
     * incremento.
     */
    public static function generarNumero(): string
    {
        $siguiente = DB::table('secuencias')
            ->where('nombre', 'venta')
            ->lockForUpdate()
            ->value('valor') + 1;

        DB::table('secuencias')->where('nombre', 'venta')->update(['valor' => $siguiente]);

        return sprintf('V-%06d', $siguiente);
    }
}
