<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'nombre' => Str::title(fake()->words(3, true)),
            'marca' => fake()->company(),
            'codigo_interno' => Str::upper(fake()->unique()->bothify('???-####')),
            'precio_referencia' => fake()->randomFloat(2, 10, 500),
            'foto' => null,
            'umbral_stock_bajo' => fake()->numberBetween(0, 5),
            'proveedor' => fake()->optional()->company(),
        ];
    }
}
