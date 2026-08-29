<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías de producto (A3.2). Son pocas y estables, y el `codigo_interno`
     * de cada producto deriva su prefijo de la categoría.
     *
     * Unicidad de `nombre` y `prefijo_codigo` **solo entre registros activos**:
     * se usa la columna generada `activo` (vale 1 si está activo, NULL si está
     * soft-deleted) dentro del índice único. MySQL admite varios NULL en un
     * índice único, así que el prefijo de una categoría eliminada puede
     * reutilizarse (Decisiones_Tecnicas_JP.md, bloque F1).
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('prefijo_codigo', 10);
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('activo')
                ->storedAs('IF(deleted_at IS NULL, 1, NULL)')
                ->nullable();

            $table->unique(['nombre', 'activo']);
            $table->unique(['prefijo_codigo', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
