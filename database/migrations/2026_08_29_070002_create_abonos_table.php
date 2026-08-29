<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abonos a una venta a crédito (RF-014; bloque C2).
     *
     * Modelo "una deuda por venta": cada abono baja
     * `ventas.credito_saldo_pendiente`. Se registra dentro de una transacción
     * con la fila `ventas` bloqueada y con la guarda
     * `monto <= credito_saldo_pendiente` (sin sobrepago).
     *
     * - `fecha`: fecha del abono declarada por el Administrador (RF-014), puede
     *   diferir de `created_at`.
     * - `usuario_id`: quién registró el abono.
     */
    public function up(): void
    {
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['venta_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos');
    }
};
