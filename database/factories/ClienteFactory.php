<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'telefono' => fake()->numerify('3## ### ####'),
            'cedula' => fake()->unique()->numerify('##########'),
            'saldo_favor' => 0,
        ];
    }
}
