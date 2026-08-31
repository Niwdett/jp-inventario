<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice sobre `ventas.fecha_venta`.
     *
     * El listado de ventas siempre ordena por `fecha_venta` (`latest`) y ahora
     * además se filtra por rango de fechas (mejora UX del módulo de ventas).
     * Sin índice ambas cosas obligan a un full scan de la tabla.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->index('fecha_venta');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['fecha_venta']);
        });
    }
};
