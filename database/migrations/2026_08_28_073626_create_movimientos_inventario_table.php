<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Libro de todo cambio de stock (bloque B3b). Fuente de auditoría:
     * `variantes.stock` es solo el valor de lectura rápida y siempre coincide
     * con el último `stock_resultante` de la variante.
     *
     * - `cantidad`: con signo (+ entra / − sale).
     * - `referencia_*`: polimórfico → la entrada, venta o ajuste que lo originó.
     * - `usuario_id`: NULL para `tipo = ajuste` (RN-15: los ajustes no registran
     *   trazabilidad de usuario).
     */
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_id')->constrained('variantes')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'venta', 'anulacion', 'devolucion', 'ajuste']);
            $table->integer('cantidad');
            $table->unsignedInteger('stock_resultante');
            $table->nullableMorphs('referencia');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['variante_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
