<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renglones de una venta (bloque E, decisiones A2 y E2).
     *
     * - `precio_unitario`: el precio real de la transacción (RN-03), no el de
     *   referencia del producto.
     * - `descuento_porcentaje`: opcional, 0–100 (RF-008).
     * - `costo_unitario_snapshot`: copia del `variantes.costo_promedio` vigente
     *   al confirmar la venta. **Nunca cambia** (A2): es la base permanente para
     *   calcular la ganancia (RN-04), inmune a cambios futuros de costo (RN-05).
     * - `importe_linea`: resultado ya resuelto y persistido (E2), calculado con
     *   aritmética decimal:
     *     round(precio_unitario * cantidad * (1 - descuento_porcentaje/100), 2)
     */
    public function up(): void
    {
        Schema::create('venta_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('variante_id')->constrained('variantes')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento_porcentaje', 5, 2)->nullable();
            $table->decimal('costo_unitario_snapshot', 12, 4);
            $table->decimal('importe_linea', 12, 2);
            $table->timestamps();

            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_lineas');
    }
};
