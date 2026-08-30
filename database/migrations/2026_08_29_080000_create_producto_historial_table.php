<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de modificaciones de un producto (RF-016, bloque B3a).
     *
     * Libro separado del historial de ventas (CLAUDE.md): registra el alta y
     * cada cambio en la información del producto, **una fila por campo
     * modificado**. Lo llena un Observer de Eloquent sobre el modelo `Producto`,
     * sin paquetes externos.
     *
     * - `usuario_id` nullable: los cambios hechos desde seeders, consola o tests
     *   no tienen un usuario autenticado (igual que `usuario_id = NULL` en los
     *   ajustes de inventario).
     * - Solo `created_at`: una fila del historial nunca se edita.
     */
    public function up(): void
    {
        Schema::create('producto_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('campo');
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['producto_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_historial');
    }
};
