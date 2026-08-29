<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes manuales de inventario (RF-006, RN-10: el conteo físico prevalece).
     *
     * **Sin `usuario_id`** (RN-15): no se registra trazabilidad de usuario en los
     * ajustes, dado que únicamente el Administrador los realiza.
     */
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_id')->constrained('variantes')->restrictOnDelete();
            $table->unsignedInteger('cantidad_anterior');
            $table->unsignedInteger('cantidad_nueva');
            $table->string('motivo')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_inventario');
    }
};
