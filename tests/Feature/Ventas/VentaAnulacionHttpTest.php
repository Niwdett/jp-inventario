<?php

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->otroVendedor = User::factory()->empleado()->create();
    $this->admin = User::factory()->administrador()->create();

    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 10,
        'costo_promedio' => '5.0000',
    ]);

    $this->venta = app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 3, 'precio_unitario' => '20.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );
});

test('el vendedor puede anular su propia venta no entregada', function () {
    $this->actingAs($this->vendedor)
        ->patch(route('ventas.anular', $this->venta), ['motivo' => 'Error de digitación'])
        ->assertRedirect(route('ventas.show', $this->venta))
        ->assertSessionHas('status');

    expect($this->venta->refresh()->estado)->toBe(EstadoVenta::Anulada)
        ->and($this->variante->refresh()->stock)->toBe(10);
});

test('el administrador puede anular la venta de cualquiera', function () {
    $this->actingAs($this->admin)
        ->patch(route('ventas.anular', $this->venta), ['motivo' => 'Solicitud del cliente'])
        ->assertRedirect(route('ventas.show', $this->venta));

    expect($this->venta->refresh()->anulada_por)->toBe($this->admin->id);
});

test('un vendedor no puede anular la venta de otro', function () {
    $this->actingAs($this->otroVendedor)
        ->patch(route('ventas.anular', $this->venta), ['motivo' => 'No es mía'])
        ->assertForbidden();

    expect($this->venta->refresh()->estado)->toBe(EstadoVenta::Confirmada);
});

test('no se puede anular una venta ya entregada', function () {
    $this->venta->forceFill(['entregada_at' => now()])->save();

    $this->actingAs($this->admin)
        ->patch(route('ventas.anular', $this->venta), ['motivo' => 'Tarde'])
        ->assertForbidden();

    expect($this->variante->refresh()->stock)->toBe(7);
});

test('el motivo de anulación es obligatorio', function () {
    $this->actingAs($this->vendedor)
        ->from(route('ventas.show', $this->venta))
        ->patch(route('ventas.anular', $this->venta), ['motivo' => ''])
        ->assertSessionHasErrors('motivo');

    expect($this->venta->refresh()->estado)->toBe(EstadoVenta::Confirmada);
});
