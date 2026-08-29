<?php

namespace Database\Factories;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Models\User;
use App\Models\Venta;
use App\Services\Ventas\RegistrarVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venta>
 *
 * Crea ventas ya construidas para las pruebas de anulación, entrega y listados.
 * El registro real de una venta (descuento de stock, snapshot de costo) se
 * ejercita a través del servicio {@see RegistrarVenta}, no
 * de esta factory.
 */
class VentaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => sprintf('V-%06d', fake()->unique()->numberBetween(1, 999999)),
            'cliente_id' => null,
            'usuario_id' => User::factory()->empleado(),
            'fecha_venta' => now(),
            'subtotal' => 0,
            'descuento_total' => 0,
            'total' => 0,
            'saldo_favor_aplicado' => 0,
            'metodo_pago' => MetodoPago::Efectivo,
            'estado' => EstadoVenta::Confirmada,
        ];
    }

    /**
     * Venta registrada por un usuario concreto.
     */
    public function deUsuario(User $usuario): static
    {
        return $this->state(fn (array $attributes) => [
            'usuario_id' => $usuario->id,
        ]);
    }

    /**
     * Venta ya entregada (ya no se puede anular; solo devolución).
     */
    public function entregada(): static
    {
        return $this->state(fn (array $attributes) => [
            'entregada_at' => now(),
        ]);
    }

    /**
     * Venta ya anulada.
     */
    public function anulada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoVenta::Anulada,
            'anulada_at' => now(),
            'anulada_por' => User::factory()->administrador(),
            'motivo_anulacion' => 'Anulación de prueba',
        ]);
    }
}
