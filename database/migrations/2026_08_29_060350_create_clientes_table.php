<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clientes del negocio (RF-013).
     *
     * En el Sprint 3 la tabla se crea **inerte**: solo existe para que la clave
     * foránea `ventas.cliente_id` sea válida (una venta de contado no exige
     * cliente, E1). La gestión de clientes, el crédito y el saldo a favor entran
     * en el Sprint 4; `saldo_favor` se deja cacheado con default 0 (C1) y no lo
     * mueve todavía ninguna lógica.
     *
     * Unicidad de `cedula` solo entre registros activos (columna generada
     * `activo`, bloque F1), permitiendo reutilizarla si un cliente se elimina.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('cedula')->nullable();
            $table->decimal('saldo_favor', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('activo')
                ->storedAs('IF(deleted_at IS NULL, 1, NULL)')
                ->nullable();

            $table->unique(['cedula', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
