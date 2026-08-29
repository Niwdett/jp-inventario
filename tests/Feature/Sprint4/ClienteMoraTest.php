<?php

use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\Venta;

it('no está en mora sin ventas a crédito', function () {
    expect(Cliente::factory()->create()->estaEnMora())->toBeFalse();
});

it('no está en mora si la deuda tiene menos de 15 días', function () {
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
        'fecha_venta' => now()->subDays(14),
    ]);

    expect($cliente->estaEnMora())->toBeFalse();
});

it('está en mora con una deuda pendiente de más de 15 días', function () {
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 40,
        'fecha_venta' => now()->subDays(16),
    ]);

    expect($cliente->estaEnMora())->toBeTrue();
});

it('deja de estar en mora cuando la deuda antigua queda saldada', function () {
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 0,
        'fecha_venta' => now()->subDays(40),
    ]);

    expect($cliente->estaEnMora())->toBeFalse();
});
