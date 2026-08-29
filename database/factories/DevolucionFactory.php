<?php

namespace Database\Factories;

use App\Enums\EstadoDevolucion;
use App\Models\Devolucion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Devolucion>
 *
 * Devolución ya construida para pruebas de listado. El proceso real (validez,
 * reintegro por línea, generación de saldo a favor con el cliente bloqueado) se
 * ejercita a través del servicio RegistrarDevolucion.
 */
class DevolucionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venta_id' => Venta::factory()->entregada(),
            'usuario_id' => User::factory()->administrador(),
            'fecha' => now()->toDateString(),
            'estado' => EstadoDevolucion::Validada,
            'motivo' => 'Defecto de fábrica',
            'saldo_generado' => 0,
        ];
    }

    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoDevolucion::Rechazada,
            'saldo_generado' => 0,
        ]);
    }
}
