<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('un empleado no puede gestionar productos', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get(route('admin.productos.index'))->assertForbidden();
    $this->actingAs($empleado)->post(route('admin.productos.store'), [])->assertForbidden();
});

test('crear sin categorías redirige a crear una categoría', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.productos.create'))
        ->assertRedirect(route('admin.categorias.create'));
});

test('el administrador crea un producto con su primera variante y código autogenerado', function () {
    $categoria = Categoria::factory()->create(['prefijo_codigo' => 'CAL']);

    $this->actingAs($this->admin)
        ->post(route('admin.productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Tenis running',
            'marca' => 'Naik',
            'precio_referencia' => '199.90',
            'umbral_stock_bajo' => 3,
            'talla' => '  40  ',
            'color' => 'Negro',
        ])
        ->assertRedirect();

    $producto = Producto::firstWhere('nombre', 'Tenis running');

    expect($producto->codigo_interno)->toBe('CAL-0001')
        ->and((float) $producto->precio_referencia)->toBe(199.90)
        ->and($producto->variantes)->toHaveCount(1)
        ->and($producto->variantes->first()->talla)->toBe('40')
        ->and($producto->variantes->first()->color)->toBe('Negro');
});

test('el correlativo del código avanza por categoría', function () {
    $categoria = Categoria::factory()->create(['prefijo_codigo' => 'ROP']);

    foreach (['Camisa', 'Pantalón'] as $nombre) {
        $this->actingAs($this->admin)->post(route('admin.productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio_referencia' => '50',
            'umbral_stock_bajo' => 0,
            'talla' => 'Única',
            'color' => 'Única',
        ]);
    }

    expect(Producto::pluck('codigo_interno')->sort()->values()->all())
        ->toBe(['ROP-0001', 'ROP-0002']);
});

test('la creación valida los datos', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.productos.create'))
        ->post(route('admin.productos.store'), [
            'categoria_id' => 999,
            'nombre' => '',
            'precio_referencia' => 'gratis',
            'umbral_stock_bajo' => -1,
            'talla' => '',
            'color' => '',
        ])
        ->assertSessionHasErrors(['categoria_id', 'nombre', 'precio_referencia', 'umbral_stock_bajo', 'talla', 'color']);
});

test('el administrador guarda la foto del producto', function () {
    Storage::fake('public');
    $categoria = Categoria::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.productos.store'), [
        'categoria_id' => $categoria->id,
        'nombre' => 'Con foto',
        'precio_referencia' => '10',
        'umbral_stock_bajo' => 0,
        'talla' => 'Única',
        'color' => 'Única',
        // ->create con mime explícito: evita depender de la extensión GD.
        'foto' => UploadedFile::fake()->create('zapato.jpg', 120, 'image/jpeg'),
    ]);

    $producto = Producto::firstWhere('nombre', 'Con foto');

    expect($producto->foto)->not->toBeNull();
    Storage::disk('public')->assertExists($producto->foto);
});

test('al editar un producto no cambian su código interno ni su categoría', function () {
    $categoria = Categoria::factory()->create(['prefijo_codigo' => 'CAL']);
    $otraCategoria = Categoria::factory()->create(['prefijo_codigo' => 'ROP']);
    $producto = Producto::factory()->for($categoria)->create(['codigo_interno' => 'CAL-0001', 'nombre' => 'Viejo']);

    $this->actingAs($this->admin)
        ->put(route('admin.productos.update', $producto), [
            'categoria_id' => $otraCategoria->id, // se ignora
            'nombre' => 'Nuevo nombre',
            'precio_referencia' => '75',
            'umbral_stock_bajo' => 1,
        ])
        ->assertRedirect(route('admin.productos.show', $producto));

    $producto->refresh();
    expect($producto->nombre)->toBe('Nuevo nombre')
        ->and($producto->codigo_interno)->toBe('CAL-0001')
        ->and($producto->categoria_id)->toBe($categoria->id);
});

test('el listado marca los productos con alguna variante en stock bajo', function () {
    $bajo = Producto::factory()->for(Categoria::factory())->create(['nombre' => 'Producto Escaso', 'umbral_stock_bajo' => 5]);
    Variante::factory()->for($bajo)->create(['stock' => 1]);

    $sano = Producto::factory()->for(Categoria::factory())->create(['nombre' => 'Producto Surtido', 'umbral_stock_bajo' => 5]);
    Variante::factory()->for($sano)->create(['stock' => 50]);

    $html = $this->actingAs($this->admin)->get(route('admin.productos.index'))->assertOk()->getContent();

    $filaEscaso = str($html)->after('Producto Escaso')->before('</tr>');
    $filaSurtido = str($html)->after('Producto Surtido')->before('</tr>');

    expect($filaEscaso->contains('Stock bajo'))->toBeTrue()
        ->and($filaSurtido->contains('Stock bajo'))->toBeFalse();
});

test('eliminar un producto arrastra sus variantes y restaurarlo las devuelve', function () {
    $producto = Producto::factory()
        ->has(Variante::factory()->count(2), 'variantes')
        ->create();

    $this->actingAs($this->admin)->delete(route('admin.productos.destroy', $producto));

    expect($producto->refresh()->trashed())->toBeTrue()
        ->and($producto->variantes()->count())->toBe(0)
        ->and($producto->variantes()->onlyTrashed()->count())->toBe(2);

    $this->actingAs($this->admin)->patch(route('admin.productos.restore', $producto));

    expect($producto->refresh()->trashed())->toBeFalse()
        ->and($producto->variantes()->count())->toBe(2);
});

test('restaurar un producto no revive una variante que se borró por separado', function () {
    $producto = Producto::factory()->has(Variante::factory()->count(2), 'variantes')->create();
    [$a, $b] = $producto->variantes->all();

    $b->delete();                  // el admin borró esta variante a mano
    $this->travel(30)->seconds();  // ...un buen rato después...
    $producto->delete();           // se elimina el producto (cascada solo a $a)
    $producto->restore();

    expect($a->refresh()->trashed())->toBeFalse()   // volvió con el producto
        ->and($b->refresh()->trashed())->toBeTrue(); // sigue borrada
});
