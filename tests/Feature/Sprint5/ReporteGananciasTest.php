<?php

use App\Enums\EstadoDevolucion;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Models\VentaLinea;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

/**
 * Línea de una venta confirmada, con su propia variante, fechada en el día dado.
 */
function s5Linea(string $fecha, float $precio, int $cantidad, float $costoSnapshot): VentaLinea
{
    $venta = Venta::factory()->create(['fecha_venta' => Carbon::parse($fecha)]);
    $variante = Variante::factory()->create(['costo_promedio' => $costoSnapshot]);

    return VentaLinea::factory()->for($venta)->create([
        'variante_id' => $variante->id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
        'descuento_porcentaje' => null,
        'costo_unitario_snapshot' => $costoSnapshot,
        'importe_linea' => round($precio * $cantidad, 2),
    ]);
}

function s5Devolucion(VentaLinea $linea, string $fecha, int $cantidad, float $valorUnitario, bool $reintegra, EstadoDevolucion $estado = EstadoDevolucion::Validada): void
{
    $devolucion = Devolucion::factory()->for($linea->venta)->create([
        'fecha' => $fecha,
        'estado' => $estado,
    ]);

    DevolucionLinea::factory()->for($devolucion)->create([
        'venta_linea_id' => $linea->id,
        'cantidad' => $cantidad,
        'reintegra_inventario' => $reintegra,
        'valor_unitario' => $valorUnitario,
    ]);
}

test('un empleado no puede ver el reporte de ganancias', function () {
    $this->actingAs(User::factory()->empleado()->create())
        ->get(route('admin.reportes.ganancias'))
        ->assertForbidden();
});

test('la ganancia bruta es el ingreso menos el costo del snapshot', function () {
    s5Linea(now()->toDateString(), precio: 20, cantidad: 3, costoSnapshot: 8);   // 60 - 24 = 36

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes']))
        ->assertOk()
        ->assertViewHas('resumen', fn ($r) => $r['ganancia_bruta'] === '36.00' && $r['ganancia_neta'] === '36.00');
});

test('usa el costo del snapshot aunque cambie el costo de la variante', function () {
    $linea = s5Linea(now()->toDateString(), precio: 20, cantidad: 1, costoSnapshot: 8);
    $linea->variante->forceFill(['costo_promedio' => '999.0000'])->save();

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes']))
        ->assertViewHas('resumen', fn ($r) => $r['ganancia_bruta'] === '12.00');
});

test('una devolucion validada con reintegro reduce la ganancia neta por el margen devuelto', function () {
    $linea = s5Linea(now()->toDateString(), precio: 20, cantidad: 2, costoSnapshot: 5);   // bruta = 40 - 10 = 30
    s5Devolucion($linea, now()->toDateString(), cantidad: 1, valorUnitario: 20, reintegra: true); // revierte 20 - 5 = 15

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes']))
        ->assertViewHas('resumen', fn ($r) => $r['ganancia_revertida'] === '15.00' && $r['ganancia_neta'] === '15.00');
});

test('una devolucion sin reintegro revierte todo el ingreso devuelto', function () {
    $linea = s5Linea(now()->toDateString(), precio: 20, cantidad: 2, costoSnapshot: 5);   // bruta = 30
    s5Devolucion($linea, now()->toDateString(), cantidad: 1, valorUnitario: 20, reintegra: false); // revierte 20 - 0 = 20

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes']))
        ->assertViewHas('resumen', fn ($r) => $r['ganancia_revertida'] === '20.00' && $r['ganancia_neta'] === '10.00');
});

test('una devolucion rechazada no afecta la ganancia', function () {
    $linea = s5Linea(now()->toDateString(), precio: 20, cantidad: 2, costoSnapshot: 5);
    s5Devolucion($linea, now()->toDateString(), cantidad: 1, valorUnitario: 20, reintegra: true, estado: EstadoDevolucion::Rechazada);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes']))
        ->assertViewHas('resumen', fn ($r) => $r['ganancia_neta'] === '30.00');
});

test('la comparacion incluye el resumen del periodo anterior', function () {
    s5Linea(now()->startOfMonth()->toDateString(), precio: 10, cantidad: 1, costoSnapshot: 4);          // mes actual: bruta 6
    s5Linea(now()->subMonthNoOverflow()->startOfMonth()->toDateString(), precio: 30, cantidad: 1, costoSnapshot: 10); // mes anterior: bruta 20

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes', 'comparar' => 1]))
        ->assertOk()
        ->assertViewHas('comparacion', fn ($c) => $c !== null && $c['resumen']['ganancia_bruta'] === '20.00');
});

test('el periodo anterior es la ventana inmediata anterior de la misma duracion', function () {
    // Preset "hoy": el periodo anterior debe ser exactamente ayer.
    s5Linea(now()->subDay()->toDateString(), precio: 50, cantidad: 1, costoSnapshot: 20);   // ayer: bruta 30
    s5Linea(now()->subDays(2)->toDateString(), precio: 99, cantidad: 1, costoSnapshot: 1);  // anteayer: fuera

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'hoy', 'comparar' => 1]))
        ->assertViewHas('comparacion', function ($c) {
            return $c['periodo']['desde']->toDateString() === now()->subDay()->toDateString()
                && $c['periodo']['hasta']->toDateString() === now()->subDay()->toDateString()
                && $c['resumen']['ganancia_bruta'] === '30.00';
        });
});

test('el detalle por producto ordena de mayor a menor ganancia neta', function () {
    s5Linea(now()->toDateString(), precio: 100, cantidad: 1, costoSnapshot: 10);   // ganancia 90
    s5Linea(now()->toDateString(), precio: 20, cantidad: 1, costoSnapshot: 5);     // ganancia 15

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ganancias', ['preset' => 'mes']))
        ->assertViewHas('porProducto', function ($porProducto) {
            return $porProducto->count() === 2
                && (float) $porProducto->first()->ganancia_neta === 90.0;
        });
});
