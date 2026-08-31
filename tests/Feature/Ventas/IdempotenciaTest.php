<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->empleado = User::factory()->empleado()->create();
    $this->variante = Variante::factory()->for(Producto::factory())->create(['stock' => 50, 'costo_promedio' => '5.0000']);
});

function payloadVentaIdem(array $overrides = []): array
{
    return array_merge([
        'metodo_pago' => 'efectivo',
        'lineas' => [
            ['variante_id' => test()->variante->id, 'cantidad' => 2, 'precio_unitario' => '25.00'],
        ],
    ], $overrides);
}

test('dos envíos con la misma clave de idempotencia crean una sola venta', function () {
    $key = (string) Str::uuid();

    $r1 = $this->actingAs($this->empleado)->post(route('ventas.store'), payloadVentaIdem(['idempotency_key' => $key]));
    $r2 = $this->actingAs($this->empleado)->post(route('ventas.store'), payloadVentaIdem(['idempotency_key' => $key]));

    expect(Venta::count())->toBe(1)
        ->and($this->variante->refresh()->stock)->toBe(48);

    $venta = Venta::sole();
    $r1->assertRedirect(route('ventas.show', $venta));
    $r2->assertRedirect(route('ventas.show', $venta));
});

test('dos envíos con claves distintas crean dos ventas', function () {
    $this->actingAs($this->empleado)->post(route('ventas.store'), payloadVentaIdem(['idempotency_key' => (string) Str::uuid()]));
    $this->actingAs($this->empleado)->post(route('ventas.store'), payloadVentaIdem(['idempotency_key' => (string) Str::uuid()]));

    expect(Venta::count())->toBe(2);
});

test('una venta sin clave de idempotencia se registra igual', function () {
    $this->actingAs($this->empleado)->post(route('ventas.store'), payloadVentaIdem());

    expect(Venta::count())->toBe(1);
});

test('una clave de idempotencia con formato inválido se rechaza', function () {
    $this->actingAs($this->empleado)
        ->from(route('ventas.create'))
        ->post(route('ventas.store'), payloadVentaIdem(['idempotency_key' => 'no-es-un-uuid']))
        ->assertSessionHasErrors('idempotency_key');

    expect(Venta::count())->toBe(0);
});

test('dos abonos con la misma clave registran un solo abono', function () {
    $venta = Venta::factory()->deUsuario($this->empleado)->create([
        'metodo_pago' => 'credito',
        'credito_monto' => '100.00',
        'credito_saldo_pendiente' => '100.00',
    ]);
    $key = (string) Str::uuid();
    $payload = ['monto' => '30.00', 'fecha' => now()->toDateString(), 'idempotency_key' => $key];

    $this->actingAs($this->empleado)->post(route('admin.creditos.abonos.store', $venta), $payload);
    $this->actingAs($this->empleado)->post(route('admin.creditos.abonos.store', $venta), $payload);

    expect($venta->abonos()->count())->toBe(1)
        ->and((float) $venta->refresh()->credito_saldo_pendiente)->toBe(70.0);
});

test('dos abonos con claves distintas se registran ambos', function () {
    $venta = Venta::factory()->deUsuario($this->empleado)->create([
        'metodo_pago' => 'credito',
        'credito_monto' => '100.00',
        'credito_saldo_pendiente' => '100.00',
    ]);

    foreach ([1, 2] as $i) {
        $this->actingAs($this->empleado)->post(route('admin.creditos.abonos.store', $venta), [
            'monto' => '30.00', 'fecha' => now()->toDateString(), 'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    expect($venta->abonos()->count())->toBe(2)
        ->and((float) $venta->refresh()->credito_saldo_pendiente)->toBe(40.0);
});
