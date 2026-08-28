<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de desarrollo. NO ejecutar en producción: para el primer admin
 * real en el hosting se usa `php artisan jp:crear-admin`.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@jp.test'],
            [
                'name' => 'Administrador JP',
                'password' => 'password',
                'rol' => Rol::Administrador,
            ],
        );

        User::firstOrCreate(
            ['email' => 'vendedor@jp.test'],
            [
                'name' => 'Vendedor JP',
                'password' => 'password',
                'rol' => Rol::Empleado,
            ],
        );
    }
}
