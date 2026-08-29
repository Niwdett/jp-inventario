<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice sobre `ventas.cliente_id` para el Sprint 4.
     *
     * En el Sprint 3 la columna se creó (E1) pero sin índice porque nada la
     * consultaba. El Sprint 4 recorre "ventas a crédito de un cliente" y
     * "¿el cliente está en mora?" (RN-09), ambas filtrando por `cliente_id`.
     *
     * La FK ya existe (`constrained('clientes')->nullOnDelete()`); esto solo
     * añade el índice de lectura.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->index(['cliente_id', 'metodo_pago']);
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['cliente_id', 'metodo_pago']);
        });
    }
};
