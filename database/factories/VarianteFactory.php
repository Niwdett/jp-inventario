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
     * Contador monotónico para las combinaciones talla/color por defecto.
     *
     * `talla` y `color` forman parte del índice único
     * `(producto_id, talla, color)` entre variantes activas. Con valores
     * aleatorios de un rango pequeño, dos variantes del mismo producto chocaban
     * de vez en cuando y hacían fallar los tests de forma intermitente. Este
     * contador recorre el espacio talla×color en orden, así que variantes
     * consecutivas creadas por la factory (p. ej. `->count(2)`) nunca colisionan.
     */
    protected static int $secuencia = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tallas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44'];
        $colores = ['Negro', 'Blanco', 'Azul', 'Rojo', 'Verde', 'Gris', 'Beige', 'Marrón', 'Amarillo', 'Rosa'];

        $n = static::$secuencia++;

        return [
            'producto_id' => Producto::factory(),
            'talla' => $tallas[$n % count($tallas)],
            'color' => $colores[intdiv($n, count($tallas)) % count($colores)],
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
