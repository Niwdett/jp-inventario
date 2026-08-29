<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('un empleado no puede ver el reporte de inventario', function () {
    $this->actingAs(User::factory()->empleado()->create())
        ->get(route('admin.reportes.inventario'))
        ->assertForbidden();
});

test('calcula el valor del inventario como stock por costo promedio', function () {
    $producto = Producto::factory()->create();
    Variante::factory()->for($producto)->create(['stock' => 4, 'costo_promedio' => '10.0000', 'talla' => 'S', 'color' => 'Azul']);
    Variante::factory()->for($producto)->create(['stock' => 2, 'costo_promedio' => '25.5000', 'talla' => 'M', 'color' => 'Azul']);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.inventario'))
        ->assertOk()
        ->assertViewHas('valorTotal', '91.00')   // 40.00 + 51.00
        ->assertViewHas('unidadesTotal', 6);
});

test('oculta las variantes agotadas salvo que se pidan', function () {
    $producto = Producto::factory()->create();
    Variante::factory()->for($producto)->create(['stock' => 5, 'talla' => 'S', 'color' => 'Rojo']);
    Variante::factory()->for($producto)->create(['stock' => 0, 'talla' => 'M', 'color' => 'Rojo']);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.inventario'))
        ->assertViewHas('variantes', fn ($v) => $v->count() === 1);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.inventario', ['incluir_agotadas' => 1]))
        ->assertViewHas('variantes', fn ($v) => $v->count() === 2);
});

test('no incluye variantes de productos eliminados', function () {
    $producto = Producto::factory()->create();
    Variante::factory()->for($producto)->create(['stock' => 9]);
    $producto->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.inventario'))
        ->assertViewHas('variantes', fn ($v) => $v->isEmpty())
        ->assertViewHas('valorTotal', '0');
});
