<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;

beforeEach(function () {
    $this->producto = Producto::factory()->create();
    $this->variante = Variante::factory()->for($this->producto)->create([
        'stock' => 10,
        'costo_promedio' => '6.0000',
    ]);
});

function payloadVenta(array $overrides = []): array
{
    return array_merge([
        'metodo_pago' => 'efectivo',
        'lineas' => [
            ['variante_id' => test()->variante->id, 'cantidad' => 2, 'precio_unitario' => '25.00'],
        ],
    ], $overrides);
}

test('un invitado es redirigido al login', function () {
    $this->get(route('ventas.create'))->assertRedirect('/login');
    $this->post(route('ventas.store'), payloadVenta())->assertRedirect('/login');
});

test('un empleado puede registrar una venta y queda asociada a él', function () {
    $empleado = User::factory()->empleado()->create();

    $response = $this->actingAs($empleado)->post(route('ventas.store'), payloadVenta());

    $venta = Venta::sole();
    $response->assertRedirect(route('ventas.show', $venta));

    expect($venta->usuario_id)->toBe($empleado->id)
        ->and($venta->numero)->toBe('V-000001')
        ->and((float) $venta->total)->toBe(50.0)
        ->and($this->variante->refresh()->stock)->toBe(8);
});

test('un administrador también puede registrar ventas', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)->post(route('ventas.store'), payloadVenta())
        ->assertRedirect(route('ventas.show', Venta::sole()));
});

test('una venta a crédito exige un cliente asociado (E1)', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadVenta(['metodo_pago' => 'credito']))
        ->assertSessionHasErrors('cliente_id');

    expect(Venta::count())->toBe(0);
});

test('valida las líneas de la venta', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), [
            'metodo_pago' => 'efectivo',
            'lineas' => [
                ['variante_id' => 999, 'cantidad' => 0, 'precio_unitario' => 'gratis'],
            ],
        ])
        ->assertSessionHasErrors(['lineas.0.variante_id', 'lineas.0.cantidad', 'lineas.0.precio_unitario']);
});

test('rechaza una variante repetida en dos líneas', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadVenta(['lineas' => [
            ['variante_id' => $this->variante->id, 'cantidad' => 1, 'precio_unitario' => '10'],
            ['variante_id' => $this->variante->id, 'cantidad' => 2, 'precio_unitario' => '10'],
        ]]))
        ->assertSessionHasErrors('lineas');
});

test('si no hay stock suficiente vuelve con un error y no registra nada', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadVenta(['lineas' => [
            ['variante_id' => $this->variante->id, 'cantidad' => 50, 'precio_unitario' => '10'],
        ]]))
        ->assertRedirect(route('ventas.create'))
        ->assertSessionHas('error');

    expect(Venta::count())->toBe(0)
        ->and($this->variante->refresh()->stock)->toBe(10);
});

test('acepta un descuento por línea', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->post(route('ventas.store'), payloadVenta(['lineas' => [
        ['variante_id' => $this->variante->id, 'cantidad' => 1, 'precio_unitario' => '100.00', 'descuento_porcentaje' => '25'],
    ]]));

    $linea = Venta::sole()->lineas->first();
    expect((float) $linea->importe_linea)->toBe(75.0)
        ->and((float) $linea->descuento_porcentaje)->toBe(25.0);
});
