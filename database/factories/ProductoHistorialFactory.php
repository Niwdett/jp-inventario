<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\ProductoHistorial;
use App\Models\User;
use App\Observers\ProductoObserver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductoHistorial>
 *
 * Entradas de historial ya construidas para pruebas de listado. El registro
 * real lo hace {@see ProductoObserver} al crear o editar un
 * producto.
 */
class ProductoHistorialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'usuario_id' => User::factory()->administrador(),
            'campo' => 'nombre',
            'valor_anterior' => fake()->words(2, true),
            'valor_nuevo' => fake()->words(2, true),
        ];
    }
}
