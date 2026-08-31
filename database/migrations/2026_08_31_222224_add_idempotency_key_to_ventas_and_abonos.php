<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clave de idempotencia para `ventas` y `abonos`.
     *
     * El formulario envía un UUID por carga de página; el servicio deduplica
     * (una segunda petición con la misma clave devuelve el registro existente
     * en vez de crear otro). El índice UNIQUE es la guarda real ante dos
     * peticiones simultáneas. Nullable: si falta (página cacheada, cliente
     * ajeno) simplemente no hay deduplicación para esa petición.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('numero');
        });

        Schema::table('abonos', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('abonos', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
