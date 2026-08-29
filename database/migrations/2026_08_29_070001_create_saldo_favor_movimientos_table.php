<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Libro del saldo a favor del cliente (bloque C1).
     *
     * Misma arquitectura que `movimientos_inventario`: ledger inmutable +
     * valor cacheado. `clientes.saldo_favor` es la lectura rápida y siempre
     * coincide con la suma de `monto` de este libro para ese cliente.
     *
     * - `tipo`: `generado` (una devolución válida abona saldo) o `aplicado`
     *   (una venta posterior lo usa como medio de pago, RF-012).
     * - `monto`: con signo (+ genera / − aplica).
     * - `referencia_*`: polimórfico → la `devoluciones` (cuando `generado`) o la
     *   `ventas` (cuando `aplicado`) que originó el movimiento.
     *
     * Todo movimiento se inserta dentro de una transacción con la fila del
     * cliente bloqueada (`lockForUpdate`), para que dos ventas simultáneas no
     * gasten el mismo saldo (C1).
     */
    public function up(): void
    {
        Schema::create('saldo_favor_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->enum('tipo', ['generado', 'aplicado']);
            $table->decimal('monto', 12, 2);
            $table->nullableMorphs('referencia');
            $table->timestamp('created_at')->nullable();

            $table->index(['cliente_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_favor_movimientos');
    }
};
