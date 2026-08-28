<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-delete en `users`: "desactivar" un usuario que ya no trabaja en el
     * negocio sin perder las ventas que registró (RN-08). Un usuario con
     * `deleted_at` no aparece en consultas normales, por lo que tampoco puede
     * iniciar sesión.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
