<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contadores con nombre para generar identificadores correlativos seguros
     * ante concurrencia (Sprint 3, sub-decisión S1).
     *
     * Cada fila es una secuencia independiente. Para obtener el siguiente valor
     * se bloquea la fila (`lockForUpdate`) dentro de la transacción que la
     * consume, se incrementa `valor` y se formatea el resultado. Así dos
     * operaciones simultáneas se serializan y nunca obtienen el mismo número,
     * y un `ROLLBACK` deshace también el incremento (sin huecos ni reutilización).
     *
     * Primer uso: el `numero` de las ventas (`V-000001`, `V-000002`, ...).
     */
    public function up(): void
    {
        Schema::create('secuencias', function (Blueprint $table) {
            $table->string('nombre')->primary();
            $table->unsignedBigInteger('valor')->default(0);
        });

        // La secuencia del número de venta debe existir siempre: se crea junto
        // con la tabla para que `Venta::generarNumero()` solo tenga que
        // bloquear e incrementar, sin resolver el caso "todavía no existe".
        DB::table('secuencias')->insert(['nombre' => 'venta', 'valor' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias');
    }
};
