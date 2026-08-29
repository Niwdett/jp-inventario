<?php

namespace Database\Factories;

use App\Models\Abono;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Abono>
 *
 * Abono suelto para pruebas de listado. El registro real (transacción, lock de
 * la venta, guarda de no sobrepago) se ejercita a través del servicio
 * RegistrarAbono.
 */
class AbonoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venta_id' => Venta::factory(),
            'monto' => fake()->randomFloat(2, 10, 200),
            'fecha' => now()->toDateString(),
            'usuario_id' => User::factory()->administrador(),
        ];
    }
}
