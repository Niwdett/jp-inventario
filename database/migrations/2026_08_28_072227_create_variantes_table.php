<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Variantes de producto por talla y color (RF-004, A3).
     *
     * - `talla` y `color`: texto libre (A3.1) — calzado y ropa usan escalas
     *   distintas. Un producto de talla/color únicos se modela como
     *   "Única" / "Única" para que ventas no tenga casos especiales.
     * - `stock`: cantidad única y global (RN-01). Es un valor de lectura rápida;
     *   la fuente de auditoría es `movimientos_inventario` (bloque B3b).
     * - `costo_promedio`: promedio ponderado móvil (A1), `decimal(12,4)` para
     *   amortiguar el redondeo del promedio.
     * - Unicidad `(producto_id, talla, color)` **solo entre variantes activas**.
     */
    public function up(): void
    {
        Schema::create('variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('talla');
            $table->string('color');
            $table->string('codigo')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('costo_promedio', 12, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('activo')
                ->storedAs('IF(deleted_at IS NULL, 1, NULL)')
                ->nullable();

            $table->unique(['producto_id', 'talla', 'color', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variantes');
    }
};
