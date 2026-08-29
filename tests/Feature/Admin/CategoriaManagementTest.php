<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('un empleado no puede gestionar categorías', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get(route('admin.categorias.index'))->assertForbidden();
    $this->actingAs($empleado)->post(route('admin.categorias.store'), [])->assertForbidden();
});

test('el administrador ve la lista de categorías', function () {
    Categoria::factory()->create(['nombre' => 'Calzado deportivo']);

    $this->actingAs($this->admin)
        ->get(route('admin.categorias.index'))
        ->assertOk()
        ->assertSee('Calzado deportivo');
});

test('el administrador crea una categoría y el prefijo se guarda en mayúsculas', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categorias.store'), [
            'nombre' => 'Calzado',
            'prefijo_codigo' => 'cal',
        ])
        ->assertRedirect(route('admin.categorias.index'));

    expect(Categoria::where('nombre', 'Calzado')->value('prefijo_codigo'))->toBe('CAL');
});

test('la creación valida los datos', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.categorias.create'))
        ->post(route('admin.categorias.store'), [
            'nombre' => '',
            'prefijo_codigo' => 'C4L',
        ])
        ->assertSessionHasErrors(['nombre', 'prefijo_codigo']);
});

test('no se repite el nombre ni el prefijo de una categoría activa', function () {
    Categoria::factory()->create(['nombre' => 'Ropa', 'prefijo_codigo' => 'ROP']);

    $this->actingAs($this->admin)
        ->post(route('admin.categorias.store'), ['nombre' => 'Ropa', 'prefijo_codigo' => 'RPA'])
        ->assertSessionHasErrors('nombre');

    $this->actingAs($this->admin)
        ->post(route('admin.categorias.store'), ['nombre' => 'Ropa interior', 'prefijo_codigo' => 'ROP'])
        ->assertSessionHasErrors('prefijo_codigo');
});

test('se puede reutilizar el prefijo de una categoría eliminada', function () {
    $categoria = Categoria::factory()->create(['prefijo_codigo' => 'ACC']);
    $categoria->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.categorias.store'), ['nombre' => 'Accesorios nuevos', 'prefijo_codigo' => 'ACC'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.categorias.index'));

    expect(Categoria::where('prefijo_codigo', 'ACC')->count())->toBe(1);
});

test('no se puede eliminar una categoría con productos activos', function () {
    $categoria = Categoria::factory()->create();
    Producto::factory()->for($categoria)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.categorias.destroy', $categoria))
        ->assertSessionHas('error');

    expect($categoria->refresh()->trashed())->toBeFalse();
});

test('se elimina y se restaura una categoría sin productos', function () {
    $categoria = Categoria::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.categorias.destroy', $categoria));
    expect($categoria->refresh()->trashed())->toBeTrue();

    $this->actingAs($this->admin)->patch(route('admin.categorias.restore', $categoria));
    expect($categoria->refresh()->trashed())->toBeFalse();
});
