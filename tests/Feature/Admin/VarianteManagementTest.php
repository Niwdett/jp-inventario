<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->producto = Producto::factory()
        ->has(Variante::factory()->state(['talla' => 'Única', 'color' => 'Única']), 'variantes')
        ->create();
});

test('un empleado no puede gestionar variantes', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->post(route('admin.productos.variantes.store', $this->producto), [])
        ->assertForbidden();
});

test('el administrador agrega una variante', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.productos.variantes.store', $this->producto), [
            'talla' => '42',
            'color' => 'Azul',
        ])
        ->assertRedirect(route('admin.productos.show', $this->producto));

    expect($this->producto->variantes()->count())->toBe(2);
});

test('no se puede duplicar la combinación talla + color', function () {
    Variante::factory()->for($this->producto)->create(['talla' => '42', 'color' => 'Azul']);

    $this->actingAs($this->admin)
        ->post(route('admin.productos.variantes.store', $this->producto), [
            'talla' => '42',
            'color' => 'Azul',
        ])
        ->assertSessionHasErrors('color');
});

test('el administrador edita una variante', function () {
    $variante = $this->producto->variantes()->first();

    $this->actingAs($this->admin)
        ->put(route('admin.productos.variantes.update', [$this->producto, $variante]), [
            'talla' => 'M',
            'color' => 'Verde',
        ])
        ->assertRedirect(route('admin.productos.show', $this->producto));

    expect($variante->refresh()->talla)->toBe('M')
        ->and($variante->color)->toBe('Verde');
});

test('no se puede eliminar la última variante de un producto', function () {
    $variante = $this->producto->variantes()->first();

    $this->actingAs($this->admin)
        ->delete(route('admin.productos.variantes.destroy', [$this->producto, $variante]))
        ->assertSessionHas('error');

    expect($this->producto->variantes()->count())->toBe(1);
});

test('se elimina una variante cuando el producto tiene más de una', function () {
    $otra = Variante::factory()->for($this->producto)->create(['talla' => 'L', 'color' => 'Rojo']);

    $this->actingAs($this->admin)
        ->delete(route('admin.productos.variantes.destroy', [$this->producto, $otra]))
        ->assertRedirect(route('admin.productos.show', $this->producto));

    expect($this->producto->variantes()->count())->toBe(1);
});

test('no se puede tocar una variante de otro producto', function () {
    $otroProducto = Producto::factory()->has(Variante::factory(), 'variantes')->create();
    $varianteAjena = $otroProducto->variantes()->first();

    $this->actingAs($this->admin)
        ->put(route('admin.productos.variantes.update', [$this->producto, $varianteAjena]), [
            'talla' => 'X', 'color' => 'Y',
        ])
        ->assertNotFound();
});
