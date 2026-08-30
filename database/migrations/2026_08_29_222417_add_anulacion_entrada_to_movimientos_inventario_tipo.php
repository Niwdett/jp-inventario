<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nuevo valor `anulacion_entrada` en `movimientos_inventario.tipo` (decisión A4).
     *
     * Movimiento compensatorio que se anexa al anular una entrada: `cantidad`
     * negativa, `usuario_id` = el Administrador que anula (a diferencia del
     * `ajuste`, sí registra usuario: es una corrección de captura, no un conteo
     * físico — RN-15). El enum no tiene soporte nativo en Schema, se altera con
     * SQL directo; el proyecto usa MySQL en dev, tests y producción.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE movimientos_inventario MODIFY tipo '.
            "ENUM('entrada', 'venta', 'anulacion', 'devolucion', 'ajuste', 'anulacion_entrada') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE movimientos_inventario MODIFY tipo '.
            "ENUM('entrada', 'venta', 'anulacion', 'devolucion', 'ajuste') NOT NULL"
        );
    }
};
