<?php

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Models\VentaLinea;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

/**
 * Venta confirmada con número, fecha y (opcionalmente) cliente concretos.
 */
function ventaListado(string $numero, string $fecha, ?Cliente $cliente = null): Venta
{
    return Venta::factory()->create([
        'numero' => $numero,
        'fecha_venta' => Carbon::parse($fecha),
        'cliente_id' => $cliente?->id,
    ]);
}

test('filtra las ventas por rango de fechas', function () {
    ventaListado('V-000001', '2026-08-05 10:00');
    ventaListado('V-000002', '2026-08-20 10:00');
    ventaListado('V-000003', '2026-09-02 10:00');

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['desde' => '2026-08-01', 'hasta' => '2026-08-31']))
        ->assertOk()
        ->assertSee('V-000001')
        ->assertSee('V-000002')
        ->assertDontSee('V-000003');
});

test('el filtro hasta incluye todo el día indicado', function () {
    ventaListado('V-000009', '2026-08-31 23:30');

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['hasta' => '2026-08-31']))
        ->assertOk()
        ->assertSee('V-000009');
});

test('acepta solo la fecha desde', function () {
    ventaListado('V-000001', '2026-07-15 10:00');
    ventaListado('V-000002', '2026-08-15 10:00');

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['desde' => '2026-08-01']))
        ->assertOk()
        ->assertDontSee('V-000001')
        ->assertSee('V-000002');
});

test('hasta no puede ser anterior a desde', function () {
    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['desde' => '2026-08-31', 'hasta' => '2026-08-01']))
        ->assertSessionHasErrors('hasta');
});

test('busca una venta por su número', function () {
    ventaListado('V-000042', '2026-08-10 10:00');
    ventaListado('V-000099', '2026-08-10 10:00');

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['buscar' => '042']))
        ->assertOk()
        ->assertSee('V-000042')
        ->assertDontSee('V-000099');
});

test('busca ventas por el nombre del cliente', function () {
    $carlos = Cliente::factory()->create(['nombre' => 'Carlos Pérez']);
    $ana = Cliente::factory()->create(['nombre' => 'Ana Gómez']);

    ventaListado('V-000001', '2026-08-10 10:00', $carlos);
    ventaListado('V-000002', '2026-08-10 10:00', $ana);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['buscar' => 'carlos']))
        ->assertOk()
        ->assertSee('V-000001')
        ->assertDontSee('V-000002');
});

test('combina la búsqueda con el rango de fechas', function () {
    $carlos = Cliente::factory()->create(['nombre' => 'Carlos Pérez']);

    ventaListado('V-000001', '2026-08-10 10:00', $carlos);   // Carlos, dentro
    ventaListado('V-000002', '2026-09-10 10:00', $carlos);   // Carlos, fuera de rango

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['buscar' => 'Carlos', 'desde' => '2026-08-01', 'hasta' => '2026-08-31']))
        ->assertOk()
        ->assertSee('V-000001')
        ->assertDontSee('V-000002');
});

test('la búsqueda no rompe el filtro de estado', function () {
    Venta::factory()->create(['numero' => 'V-000001', 'fecha_venta' => Carbon::parse('2026-08-10')]);
    Venta::factory()->anulada()->create(['numero' => 'V-000002', 'fecha_venta' => Carbon::parse('2026-08-11')]);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['estado' => 'anulada', 'desde' => '2026-08-01']))
        ->assertOk()
        ->assertSee('V-000002')
        ->assertDontSee('V-000001');
});

test('el empleado sigue viendo solo sus ventas al aplicar filtros', function () {
    $empleado = User::factory()->empleado()->create();
    $otro = User::factory()->empleado()->create();

    Venta::factory()->deUsuario($empleado)->create(['numero' => 'V-000001', 'fecha_venta' => Carbon::parse('2026-08-10')]);
    Venta::factory()->deUsuario($otro)->create(['numero' => 'V-000002', 'fecha_venta' => Carbon::parse('2026-08-10')]);

    $this->actingAs($empleado)
        ->get(route('ventas.index', ['desde' => '2026-08-01', 'hasta' => '2026-08-31']))
        ->assertOk()
        ->assertSee('V-000001')
        ->assertDontSee('V-000002');
});

test('la paginación conserva los filtros aplicados', function () {
    Cliente::factory()->create(['nombre' => 'Cliente Buscado']);

    $cliente = Cliente::firstWhere('nombre', 'Cliente Buscado');
    Venta::factory()->count(21)->sequence(
        fn ($sequence) => ['numero' => sprintf('V-%06d', $sequence->index + 1)],
    )->create(['cliente_id' => $cliente->id, 'fecha_venta' => Carbon::parse('2026-08-10')]);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['buscar' => 'Buscado']))
        ->assertOk()
        ->assertSee('buscar=Buscado', false);
});

test('el listado muestra un resumen de los productos vendidos', function () {
    $venta = ventaListado('V-000123', '2026-08-10 10:00');

    $camiseta = Variante::factory()->for(Producto::factory()->create(['nombre' => 'Camiseta básica']))->create();
    $jean = Variante::factory()->for(Producto::factory()->create(['nombre' => 'Jean clásico']))->create();

    VentaLinea::factory()->for($venta)->paraVariante($camiseta, 2, 20)->create();
    VentaLinea::factory()->for($venta)->paraVariante($jean, 1, 80)->create();

    $this->actingAs($this->admin)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Camiseta básica ×2')
        ->assertSee('Jean clásico ×1');
});

test('resumenProductos corta la lista cuando hay muchas líneas', function () {
    $venta = Venta::factory()->create();

    foreach (['A', 'B', 'C', 'D', 'E'] as $nombre) {
        $variante = Variante::factory()->for(Producto::factory()->create(['nombre' => "Producto {$nombre}"]))->create();
        VentaLinea::factory()->for($venta)->paraVariante($variante, 1, 10)->create();
    }

    $venta->load('lineas.variante.producto');

    expect($venta->resumenProductos())->toBe('Producto A ×1, Producto B ×1, Producto C ×1 +2 más');
});
