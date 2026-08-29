<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entradas de mercancía (RF-005). Registro histórico de compras: `cantidad`
     * y `costo_unitario` no cambian nunca — son la auditoría del recálculo del
     * costo promedio ponderado móvil (A1, A2).
     */
    public function up(): void
    {
        Schema::create('entradas_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_id')->constrained('variantes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('costo_unitario', 12, 4);
            $table->date('fecha');
            $table->string('proveedor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas_inventario');
    }
};
