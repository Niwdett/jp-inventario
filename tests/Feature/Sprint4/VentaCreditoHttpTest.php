<?php

use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;

beforeEach(function () {
    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 20,
        'costo_promedio' => '4.0000',
    ]);
});

function payloadCredito(array $overrides = []): array
{
    return array_merge([
        'metodo_pago' => 'credito',
        'lineas' => [
            ['variante_id' => test()->variante->id, 'cantidad' => 1, 'precio_unitario' => '100.00'],
        ],
    ], $overrides);
}

test('un empleado registra una venta a crédito a un cliente', function () {
    $empleado = User::factory()->empleado()->create();
    $cliente = Cliente::factory()->create();

    $this->actingAs($empleado)
        ->post(route('ventas.store'), payloadCredito(['cliente_id' => $cliente->id]))
        ->assertRedirect(route('ventas.show', Venta::sole()));

    expect((float) Venta::sole()->credito_saldo_pendiente)->toBe(100.0);
});

test('el saldo a favor no puede superar el total (validación)', function () {
    $empleado = User::factory()->empleado()->create();
    $cliente = Cliente::factory()->create(['saldo_favor' => 500]);

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadCredito([
            'metodo_pago' => 'efectivo',
            'cliente_id' => $cliente->id,
            'saldo_favor_aplicado' => '150',
        ]))
        ->assertSessionHasErrors('saldo_favor_aplicado');

    expect(Venta::count())->toBe(0);
});

test('aplicar saldo a favor sin cliente falla la validación', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadCredito([
            'metodo_pago' => 'efectivo',
            'saldo_favor_aplicado' => '10',
        ]))
        ->assertSessionHasErrors('cliente_id');
});

test('un empleado no puede forzar crédito a un cliente en mora', function () {
    $empleado = User::factory()->empleado()->create();
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
        'fecha_venta' => now()->subDays(30),
    ]);

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadCredito(['cliente_id' => $cliente->id]))
        ->assertRedirect(route('ventas.create'))
        ->assertSessionHas('error');

    expect(Venta::where('numero', 'like', 'V-%')->count())->toBe(1);
});

test('un administrador autoriza el crédito en mora con la casilla', function () {
    $admin = User::factory()->administrador()->create();
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
        'fecha_venta' => now()->subDays(30),
    ]);

    $this->actingAs($admin)
        ->post(route('ventas.store'), payloadCredito([
            'cliente_id' => $cliente->id,
            'autorizar_mora' => '1',
        ]))
        ->assertRedirect();

    $nueva = Venta::latest('id')->first();
    expect($nueva->credito_autorizado_por)->toBe($admin->id);
});
