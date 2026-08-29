<?php

namespace Database\Factories;

use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\VentaLinea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevolucionLinea>
 */
class DevolucionLineaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'devolucion_id' => Devolucion::factory(),
            'venta_linea_id' => VentaLinea::factory(),
            'cantidad' => 1,
            'reintegra_inventario' => true,
            'valor_unitario' => fake()->randomFloat(2, 10, 200),
        ];
    }
}
