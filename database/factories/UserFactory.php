<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => Rol::Empleado,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Usuario con rol Administrador.
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Administrador,
        ]);
    }

    /**
     * Usuario con rol Empleado / Vendedor.
     */
    public function empleado(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Empleado,
        ]);
    }
}
