<?php

use App\Enums\MetodoPago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 10,
        'costo_promedio' => '5.0000',
    ]);
    $this->venta = app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 2, 'precio_unitario' => '20.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );
});

test('el vendedor puede marcar su venta como entregada', function () {
    $this->actingAs($this->vendedor)
        ->patch(route('ventas.entregar', $this->venta))
        ->assertRedirect(route('ventas.show', $this->venta))
        ->assertSessionHas('status');

    expect($this->venta->refresh()->entregada_at)->not->toBeNull();
});

test('una venta entregada ya no se puede volver a entregar', function () {
    $this->venta->forceFill(['entregada_at' => now()])->save();

    $this->actingAs($this->vendedor)
        ->patch(route('ventas.entregar', $this->venta))
        ->assertForbidden();
});

test('tras la entrega, anular queda bloqueado', function () {
    $this->actingAs($this->vendedor)->patch(route('ventas.entregar', $this->venta));

    $this->actingAs($this->vendedor)
        ->patch(route('ventas.anular', $this->venta), ['motivo' => 'Ya no la quiere'])
        ->assertForbidden();

    expect($this->variante->refresh()->stock)->toBe(8);
});
