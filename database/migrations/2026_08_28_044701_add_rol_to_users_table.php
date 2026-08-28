<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el rol del usuario (RF-001). Dos valores fijos; sin tabla `roles`.
     * El default es `empleado`: el rol de menor privilegio, para que un alta
     * sin rol explícito nunca conceda acceso administrativo por accidente.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['administrador', 'empleado'])
                ->default('empleado')
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};
