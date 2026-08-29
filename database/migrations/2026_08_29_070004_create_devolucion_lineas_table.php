<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detalle de una devolución (E3; flujo 4.4).
     *
     * Una fila por cada `venta_lineas` que el cliente regresa.
     *
     * - `reintegra_inventario`: el Administrador decide por línea si la unidad
     *   vuelve al stock (`true` → movimiento `tipo=devolucion`, +cantidad) o se
     *   da de baja por daño (`false` → el stock no cambia). RN-13.
     * - `valor_unitario`: lo que el cliente pagó por unidad de esa línea
     *   (`venta_lineas.importe_linea / cantidad`). Base del saldo a favor
     *   generado; el Administrador no lo edita en el MVP.
     *
     * Guarda de negocio (en el servicio, no en la BD): la suma de `cantidad`
     * devuelta por `venta_linea_id` nunca supera la cantidad vendida.
     */
    public function up(): void
    {
        Schema::create('devolucion_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
            $table->foreignId('venta_linea_id')->constrained('venta_lineas')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->boolean('reintegra_inventario');
            $table->decimal('valor_unitario', 12, 2);

            $table->index('venta_linea_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_lineas');
    }
};
