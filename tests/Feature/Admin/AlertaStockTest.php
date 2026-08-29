<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('un empleado no puede ver las alertas de stock', function () {
    $this->actingAs(User::factory()->empleado()->create())
        ->get(route('admin.inventario.alertas.index'))
        ->assertForbidden();
});

test('lista solo las variantes activas en o bajo el umbral de su producto', function () {
    $producto = Producto::factory()->create(['nombre' => 'Camiseta', 'umbral_stock_bajo' => 3]);
    $baja = Variante::factory()->for($producto)->create(['stock' => 2, 'talla' => 'S', 'color' => 'Rojo']);
    $justo = Variante::factory()->for($producto)->create(['stock' => 3, 'talla' => 'M', 'color' => 'Rojo']);
    $ok = Variante::factory()->for($producto)->create(['stock' => 10, 'talla' => 'L', 'color' => 'Rojo']);

    $response = $this->actingAs($this->admin)->get(route('admin.inventario.alertas.index'))->assertOk();

    $response->assertViewHas('variantes', function ($variantes) use ($baja, $justo, $ok) {
        $ids = $variantes->pluck('id');

        return $ids->contains($baja->id)
            && $ids->contains($justo->id)
            && ! $ids->contains($ok->id);
    });
});

test('no alerta sobre variantes de productos eliminados', function () {
    $producto = Producto::factory()->create(['umbral_stock_bajo' => 5]);
    Variante::factory()->for($producto)->create(['stock' => 0]);
    $producto->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.alertas.index'))
        ->assertViewHas('variantes', fn ($variantes) => $variantes->isEmpty());
});

test('el libro de movimientos es accesible para el administrador y no para el empleado', function () {
    $this->actingAs($this->admin)->get(route('admin.inventario.movimientos.index'))->assertOk();
    $this->actingAs(User::factory()->empleado()->create())
        ->get(route('admin.inventario.movimientos.index'))->assertForbidden();
});
