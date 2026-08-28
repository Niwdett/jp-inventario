<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea el primer usuario Administrador (bootstrap del sistema).
 *
 * En el sistema JP nadie puede registrarse solo: los usuarios los crea un
 * Administrador desde la aplicación. Este comando resuelve el "huevo y la
 * gallina" en una instalación nueva, tanto en local como en el hosting.
 *
 * Interactivo:   php artisan jp:crear-admin
 * No interactivo: php artisan jp:crear-admin --name="Ana" --email="ana@jp.co" --password="secreta12"
 */
class CrearAdminCommand extends Command
{
    protected $signature = 'jp:crear-admin
                            {--name= : Nombre del administrador}
                            {--email= : Correo del administrador}
                            {--password= : Contraseña (mínimo 8 caracteres)}';

    protected $description = 'Crea un usuario con rol Administrador';

    public function handle(): int
    {
        $name = $this->option('name') ?: text(
            label: 'Nombre del administrador',
            required: true,
        );

        $email = $this->option('email') ?: text(
            label: 'Correo',
            required: true,
        );

        $password = $this->option('password') ?: password(
            label: 'Contraseña (mínimo 8 caracteres)',
            required: true,
        );

        $datos = [
            'name' => $name,
            'email' => mb_strtolower(trim($email)),
            'password' => $password,
        ];

        $validator = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            ...$datos,
            'rol' => Rol::Administrador,
        ]);

        $this->info("Administrador creado: {$user->name} <{$user->email}>");

        return self::SUCCESS;
    }
}
