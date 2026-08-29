<?php

namespace Database\Factories;

use App\Enums\TipoSaldoFavor;
use App\Models\Cliente;
use App\Models\SaldoFavorMovimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaldoFavorMovimiento>
 *
 * Renglón suelto del libro para pruebas de listado. El movimiento real (con la
 * fila del cliente bloqueada y `clientes.saldo_favor` actualizado en la misma
 * transacción) se ejercita a través del servicio MovimientoSaldoFavor.
 */
class SaldoFavorMovimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'tipo' => TipoSaldoFavor::Generado,
            'monto' => fake()->randomFloat(2, 10, 300),
            'referencia_type' => null,
            'referencia_id' => null,
        ];
    }

    public function aplicado(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoSaldoFavor::Aplicado,
            'monto' => -1 * abs((float) $attributes['monto']),
        ]);
    }
}
