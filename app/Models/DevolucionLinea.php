<?php

namespace App\Models;

use Database\Factories\DevolucionLineaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detalle de una devolución (E3): una fila por `venta_lineas` que el cliente
 * regresa. La escribe el servicio RegistrarDevolucion.
 *
 * - `reintegra_inventario`: el Administrador decide por línea si la unidad
 *   vuelve al stock (RN-13).
 * - `valor_unitario`: lo que el cliente pagó por unidad
 *   (`venta_lineas.importe_linea / cantidad`); base del saldo a favor.
 */
#[Fillable(['devolucion_id', 'venta_linea_id', 'cantidad', 'reintegra_inventario', 'valor_unitario'])]
class DevolucionLinea extends Model
{
    /** @use HasFactory<DevolucionLineaFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'devolucion_lineas';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'reintegra_inventario' => 'boolean',
            'valor_unitario' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Devolucion, $this>
     */
    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class);
    }

    /**
     * @return BelongsTo<VentaLinea, $this>
     */
    public function ventaLinea(): BelongsTo
    {
        return $this->belongsTo(VentaLinea::class);
    }
}
