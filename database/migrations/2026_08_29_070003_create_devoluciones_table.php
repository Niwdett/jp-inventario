<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Devoluciones de mercancía ya entregada (RF-011; flujo 4.4).
     *
     * Distinta de la anulación (RF-010): la anulación cancela una venta **antes**
     * de la entrega y reintegra stock automáticamente; la devolución ocurre
     * **después** de la entrega, la valida el Administrador y genera saldo a
     * favor (nunca efectivo, RN-11).
     *
     * - `estado`: `validada` (generó saldo a favor y, por línea, pudo reintegrar
     *   stock) o `rechazada` (se registra para auditoría; `saldo_generado = 0`,
     *   no toca stock).
     * - `saldo_generado`: Σ (`devolucion_lineas.valor_unitario` × `cantidad`).
     * - `usuario_id`: el Administrador que la validó.
     *
     * No se pueden anular ni editar una vez creadas (fuera de alcance del MVP).
     */
    public function up(): void
    {
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->enum('estado', ['validada', 'rechazada']);
            $table->string('motivo');
            $table->decimal('saldo_generado', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['venta_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};
