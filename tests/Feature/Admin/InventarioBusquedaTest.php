<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Inventario\AjustarInventario;
use App\Services\Inventario\RegistrarEntrada;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();

    $this->camisa = Variante::factory()
        ->for(Producto::factory()->create(['nombre' => 'Camisa Oxford', 'codigo_interno' => 'CAM-0001']))
        ->create(['stock' => 0, 'costo_promedio' => 0]);

    $this->jean = Variante::factory()
        ->for(Producto::factory()->create(['nombre' => 'Jean Clásico', 'codigo_interno' => 'PAN-0009']))
        ->create(['stock' => 0, 'costo_promedio' => 0]);
});

function entradaDemo(Variante $variante): void
{
    app(RegistrarEntrada::class)->ejecutar($variante, 5, '10', now()->toDateString(), null, test()->admin);
}

test('la búsqueda de entradas filtra por nombre de producto', function () {
    entradaDemo($this->camisa);
    entradaDemo($this->jean);

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.entradas.index', ['buscar' => 'camisa']))
        ->assertOk()
        ->assertSee('Camisa Oxford')
        ->assertDontSee('Jean Clásico');
});

test('la búsqueda de entradas también acepta el código del producto', function () {
    entradaDemo($this->camisa);
    entradaDemo($this->jean);

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.entradas.index', ['buscar' => 'PAN-0009']))
        ->assertOk()
        ->assertSee('Jean Clásico')
        ->assertDontSee('Camisa Oxford');
});

test('la búsqueda de movimientos filtra por producto', function () {
    entradaDemo($this->camisa);
    entradaDemo($this->jean);

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.movimientos.index', ['buscar' => 'jean']))
        ->assertOk()
        ->assertSee('Jean Clásico')
        ->assertDontSee('Camisa Oxford');
});

test('la búsqueda de ajustes filtra por producto', function () {
    app(AjustarInventario::class)->ejecutar($this->camisa, 8, 'conteo');
    app(AjustarInventario::class)->ejecutar($this->jean, 4, 'conteo');

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.ajustes.index', ['buscar' => 'camisa']))
        ->assertOk()
        ->assertSee('Camisa Oxford')
        ->assertDontSee('Jean Clásico');
});

test('una búsqueda sin coincidencias muestra el estado vacío con opción de limpiar', function () {
    entradaDemo($this->camisa);

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.entradas.index', ['buscar' => 'zzz']))
        ->assertOk()
        ->assertDontSee('Camisa Oxford')
        ->assertSee('Ver todas');
});

test('la búsqueda se conserva en la paginación', function () {
    Variante::factory()->count(25)
        ->for(Producto::factory()->create(['nombre' => 'Medias Deportivas']))
        ->create(['stock' => 0, 'costo_promedio' => 0])
        ->each(fn (Variante $v) => entradaDemo($v));

    $this->actingAs($this->admin)
        ->get(route('admin.inventario.entradas.index', ['buscar' => 'Medias']))
        ->assertOk()
        ->assertSee('buscar=Medias', false);
});
