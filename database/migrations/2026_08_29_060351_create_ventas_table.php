<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ventas (RF-008, RF-009, RF-010; bloque E).
     *
     * Las ventas **nunca se eliminan** (B2): la anulación y la devolución son
     * cambios de estado, no borrados. Por eso no hay `softDeletes`.
     *
     * El Sprint 3 solo usa las ventas de contado (`metodo_pago` efectivo o
     * transferencia, sin cliente obligatorio). Las columnas de crédito y de
     * saldo a favor se crean ahora —según el bloque E— pero ninguna lógica de
     * este sprint las escribe; entran en funcionamiento en el Sprint 4.
     *
     * - `numero`: correlativo global `V-000001`, único permanentemente (S1).
     * - `usuario_id`: quién registró la venta (RN-08), obligatorio.
     * - `entregada_at`: si es NULL la venta puede anularse; si tiene fecha solo
     *   admite devolución (regla anulación vs. devolución, bloque E).
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_venta');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('saldo_favor_aplicado', 12, 2)->default(0);

            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'credito']);
            $table->enum('estado', ['confirmada', 'anulada'])->default('confirmada');

            $table->dateTime('entregada_at')->nullable();

            $table->dateTime('anulada_at')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion')->nullable();

            // Crédito (Sprint 4). Se llenan solo cuando metodo_pago = credito.
            $table->decimal('credito_monto', 12, 2)->nullable();
            $table->decimal('credito_saldo_pendiente', 12, 2)->nullable();
            $table->foreignId('credito_autorizado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('estado');
            $table->index('usuario_id');
            $table->index('entregada_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
