<?php

use App\Enums\MetodoPago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->vendedorA = User::factory()->empleado()->create();
    $this->vendedorB = User::factory()->empleado()->create();
    $this->admin = User::factory()->administrador()->create();

    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 50,
        'costo_promedio' => '5.0000',
    ]);

    $linea = [['variante_id' => $this->variante->id, 'cantidad' => 1, 'precio_unitario' => '20.00', 'descuento_porcentaje' => null]];

    $registrar = app(RegistrarVenta::class);
    $this->ventaA = $registrar->ejecutar($linea, MetodoPago::Efectivo, null, $this->vendedorA);
    $this->ventaB = $registrar->ejecutar($linea, MetodoPago::Efectivo, null, $this->vendedorB);
});

test('el listado del vendedor solo muestra sus propias ventas', function () {
    $this->actingAs($this->vendedorA)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee($this->ventaA->numero)
        ->assertDontSee($this->ventaB->numero);
});

test('el administrador ve todas las ventas en el listado', function () {
    $this->actingAs($this->admin)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee($this->ventaA->numero)
        ->assertSee($this->ventaB->numero);
});

test('un vendedor no puede ver el detalle de la venta de otro', function () {
    $this->actingAs($this->vendedorA)
        ->get(route('ventas.show', $this->ventaB))
        ->assertForbidden();
});

test('el administrador puede ver el detalle de cualquier venta', function () {
    $this->actingAs($this->admin)
        ->get(route('ventas.show', $this->ventaA))
        ->assertOk()
        ->assertSee($this->ventaA->numero);
});

test('un invitado no accede al módulo de ventas', function () {
    $this->get(route('ventas.index'))->assertRedirect('/login');
});
