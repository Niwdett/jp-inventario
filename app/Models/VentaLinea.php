<?php

namespace App\Models;

use App\Enums\EstadoDevolucion;
use App\Services\Ventas\RegistrarVenta;
use Database\Factories\VentaLineaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Renglón de una venta (bloque E, A2, E2).
 *
 * `costo_unitario_snapshot` e `importe_linea` se fijan al confirmar la venta y
 * no cambian nunca: son la base permanente e inmutable del cálculo de ganancia
 * (RN-04, RN-05). Los escribe {@see RegistrarVenta}.
 */
#[Fillable(['venta_id', 'variante_id', 'cantidad', 'precio_unitario', 'descuento_porcentaje', 'costo_unitario_snapshot', 'importe_linea'])]
class VentaLinea extends Model
{
    /** @use HasFactory<VentaLineaFactory> */
    use HasFactory;

    protected $table = 'venta_lineas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'costo_unitario_snapshot' => 'decimal:4',
            'importe_linea' => 'decimal:2',
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
     * @return BelongsTo<Variante, $this>
     */
    public function variante(): BelongsTo
    {
        return $this->belongsTo(Variante::class);
    }

    /**
     * @return HasMany<DevolucionLinea, $this>
     */
    public function devolucionLineas(): HasMany
    {
        return $this->hasMany(DevolucionLinea::class);
    }

    /**
     * Unidades de esta línea ya devueltas en devoluciones validadas. La guarda
     * del servicio de devolución impide que una nueva devolución supere
     * `cantidad − cantidadDevuelta()`.
     */
    public function cantidadDevuelta(): int
    {
        return (int) $this->devolucionLineas()
            ->whereHas('devolucion', fn ($query) => $query->where('estado', EstadoDevolucion::Validada))
            ->sum('cantidad');
    }

    /**
     * Lo que el cliente pagó por unidad de esta línea (importe con descuento
     * aplicado / cantidad). Base del `valor_unitario` de una devolución.
     */
    public function valorUnitarioPagado(): string
    {
        return bcdiv((string) $this->importe_linea, (string) $this->cantidad, 2);
    }

    /**
     * Ganancia de esta línea (RN-04):
     *   (importe_linea) − (costo_unitario_snapshot · cantidad)
     *
     * Puede ser negativa (RN-04 lo permite). Cálculo con aritmética decimal.
     * Solo lectura; los reportes que la consumen llegan en el Sprint 5.
     */
    public function ganancia(): string
    {
        $costoTotal = bcmul((string) $this->costo_unitario_snapshot, (string) $this->cantidad, 2);

        return bcsub((string) $this->importe_linea, $costoTotal, 2);
    }
}
