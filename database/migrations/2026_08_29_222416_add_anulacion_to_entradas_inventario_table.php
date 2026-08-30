<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anulación de una entrada de mercancía (decisión A4).
     *
     * La fila de `entradas_inventario` nunca se borra ni se edita: una entrada
     * mal capturada se marca como anulada (espejo de la anulación de ventas, B2)
     * y `variantes.costo_promedio`/`stock` se reconstruyen reproduciendo el libro
     * de movimientos de la variante.
     */
    public function up(): void
    {
        Schema::table('entradas_inventario', function (Blueprint $table) {
            $table->dateTime('anulada_at')->nullable()->after('proveedor');
            $table->foreignId('anulada_por')->nullable()->after('anulada_at')->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion')->nullable()->after('anulada_por');
        });
    }

    public function down(): void
    {
        Schema::table('entradas_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropColumn(['anulada_at', 'motivo_anulacion']);
        });
    }
};
