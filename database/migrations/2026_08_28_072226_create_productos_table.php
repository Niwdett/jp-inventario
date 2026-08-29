<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Productos del catálogo (RF-003, A3).
     *
     * - `codigo_interno`: autogenerado como `PREFIJO-NNNN` a partir de la
     *   categoría; único **solo entre productos activos** (columna generada
     *   `activo` + índice único, bloque F1).
     * - `umbral_stock_bajo`: el umbral de alerta vive en el producto, no en la
     *   variante (RN-14: configurable por producto).
     * - `precio_referencia` y todos los importes: `decimal`, nunca `float`.
     * - `proveedor`: campo simple de texto en el MVP; la gestión real de
     *   proveedores es V2.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('nombre');
            $table->string('marca')->nullable();
            $table->string('codigo_interno');
            $table->decimal('precio_referencia', 12, 2);
            $table->string('foto')->nullable();
            $table->unsignedInteger('umbral_stock_bajo')->default(0);
            $table->string('proveedor')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('activo')
                ->storedAs('IF(deleted_at IS NULL, 1, NULL)')
                ->nullable();

            $table->unique(['codigo_interno', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
