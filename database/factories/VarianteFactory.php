<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Variante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Variante>
 */
class VarianteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'talla' => (string) fake()->numberBetween(35, 45),
            'color' => fake()->safeColorName(),
            'codigo' => null,
            'stock' => fake()->numberBetween(0, 50),
            'costo_promedio' => fake()->randomFloat(4, 5, 200),
        ];
    }

    /**
     * Variante de talla y color únicos (A3).
     */
    public function unica(): static
    {
        return $this->state(fn (array $attributes) => [
            'talla' => 'Única',
            'color' => 'Única',
        ]);
    }

    /**
     * Variante sin stock.
     */
    public function sinStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
