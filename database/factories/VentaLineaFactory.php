<?php

namespace Database\Factories;

use App\Models\Variante;
use App\Models\Venta;
use App\Models\VentaLinea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VentaLinea>
 */
class VentaLineaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 5);
        $precio = fake()->randomFloat(2, 10, 300);
        $importe = round($precio * $cantidad, 2);

        return [
            'venta_id' => Venta::factory(),
            'variante_id' => Variante::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'descuento_porcentaje' => null,
            'costo_unitario_snapshot' => fake()->randomFloat(4, 5, 150),
            'importe_linea' => $importe,
        ];
    }

    /**
     * Línea que vende una variante concreta, con cantidad y precio dados.
     */
    public function paraVariante(Variante $variante, int $cantidad, float $precioUnitario): static
    {
        return $this->state(fn (array $attributes) => [
            'variante_id' => $variante->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'costo_unitario_snapshot' => $variante->costo_promedio,
            'importe_linea' => round($precioUnitario * $cantidad, 2),
        ]);
    }
}
