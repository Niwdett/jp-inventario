<?php

use App\Models\Producto;
use App\Models\ProductoHistorial;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('crear un producto registra una entrada de alta', function () {
    $producto = Producto::factory()->create();

    $entradas = $producto->historial()->get();

    expect($entradas)->toHaveCount(1)
        ->and($entradas->first()->campo)->toBe('alta')
        ->and($entradas->first()->valor_anterior)->toBeNull()
        ->and($entradas->first()->valor_nuevo)->toBeNull();
});

test('editar un campo auditado registra su valor anterior y el nuevo', function () {
    $producto = Producto::factory()->create(['nombre' => 'Camisa lisa']);
    $producto->historial()->delete();

    $producto->update(['nombre' => 'Camisa a rayas']);

    $entrada = $producto->historial()->sole();

    expect($entrada->campo)->toBe('nombre')
        ->and($entrada->valor_anterior)->toBe('Camisa lisa')
        ->and($entrada->valor_nuevo)->toBe('Camisa a rayas');
});

test('un cambio simultáneo de varios campos genera una fila por campo', function () {
    $producto = Producto::factory()->create(['marca' => 'Acme', 'umbral_stock_bajo' => 3]);
    $producto->historial()->delete();

    $producto->update(['marca' => 'Globex', 'umbral_stock_bajo' => 5, 'proveedor' => 'Mayorista Sur']);

    expect($producto->historial()->pluck('campo')->sort()->values()->all())
        ->toBe(['marca', 'proveedor', 'umbral_stock_bajo']);
});

test('guardar sin cambios reales no registra nada', function () {
    $producto = Producto::factory()->create(['nombre' => 'Buzo']);
    $producto->historial()->delete();

    $producto->update(['nombre' => 'Buzo']);

    expect($producto->historial()->count())->toBe(0);
});

test('el codigo interno y la categoria no se auditan', function () {
    $producto = Producto::factory()->create();
    $producto->historial()->delete();

    // Cambio directo en BD (no pasa por el Form Request, que los bloquea):
    $producto->forceFill(['codigo_interno' => 'ZZZ-9999'])->save();

    expect($producto->historial()->count())->toBe(0);
});

test('desactivar y reactivar un producto deja rastro del estado', function () {
    $producto = Producto::factory()->create();
    $producto->historial()->delete();

    $producto->delete();
    $producto->restore();

    $estados = $producto->historial()->where('campo', 'estado')->orderBy('id')->get();

    expect($estados)->toHaveCount(2)
        ->and($estados[0]->valor_anterior)->toBe('activo')
        ->and($estados[0]->valor_nuevo)->toBe('inactivo')
        ->and($estados[1]->valor_anterior)->toBe('inactivo')
        ->and($estados[1]->valor_nuevo)->toBe('activo');
});

test('registra el usuario autenticado que hizo el cambio', function () {
    $producto = Producto::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.productos.update', $producto), [
            'nombre' => 'Nombre nuevo',
            'precio_referencia' => $producto->precio_referencia,
            'umbral_stock_bajo' => $producto->umbral_stock_bajo,
        ])
        ->assertRedirect();

    expect($producto->historial()->where('campo', 'nombre')->sole()->usuario_id)
        ->toBe($this->admin->id);
});

test('los cambios fuera de una sesion quedan sin usuario', function () {
    $producto = Producto::factory()->create();

    expect(ProductoHistorial::where('campo', 'alta')->sole()->usuario_id)->toBeNull();
});

test('un empleado no puede ver el historial y el administrador si', function () {
    $producto = Producto::factory()->create();

    $this->actingAs(User::factory()->empleado()->create())
        ->get(route('admin.productos.historial', $producto))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('admin.productos.historial', $producto))
        ->assertOk()
        ->assertSee('Alta del producto');
});

test('el historial sigue disponible tras eliminar el producto', function () {
    $producto = Producto::factory()->create();
    $producto->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.productos.historial', $producto))
        ->assertOk();
});
