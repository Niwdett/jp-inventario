<?php

use App\Enums\MetodoPago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

/**
 * Venta confirmada con un total dado, fechada en el día indicado.
 */
function s5Venta(string $fecha, float $total, MetodoPago $metodo = MetodoPago::Efectivo): Venta
{
    return Venta::factory()->create([
        'fecha_venta' => Carbon::parse($fecha),
        'subtotal' => $total,
        'total' => $total,
        'metodo_pago' => $metodo,
    ]);
}

test('un empleado no puede ver el reporte de ventas', function () {
    $this->actingAs(User::factory()->empleado()->create())
        ->get(route('admin.reportes.ventas'))
        ->assertForbidden();
});

test('el resumen del mes suma solo las ventas confirmadas del periodo', function () {
    $hoy = now();

    s5Venta($hoy->toDateString(), 100);
    s5Venta($hoy->copy()->startOfMonth()->toDateString(), 50);
    s5Venta($hoy->copy()->subMonth()->toDateString(), 999);            // fuera de rango
    Venta::factory()->anulada()->create([                              // anulada: no cuenta
        'fecha_venta' => $hoy,
        'total' => 777,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ventas', ['preset' => 'mes']))
        ->assertOk()
        ->assertViewHas('resumen', fn ($resumen) => (int) $resumen->ventas === 2
            && (float) $resumen->total === 150.0);
});

test('agrupa las ventas por metodo de pago', function () {
    $hoy = now()->toDateString();
    s5Venta($hoy, 100, MetodoPago::Efectivo);
    s5Venta($hoy, 40, MetodoPago::Efectivo);
    s5Venta($hoy, 30, MetodoPago::Transferencia);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ventas', ['preset' => 'hoy']))
        ->assertOk()
        ->assertViewHas('porMetodo', function ($porMetodo) {
            $efectivo = $porMetodo->firstWhere('metodo_pago', MetodoPago::Efectivo);

            return (int) $efectivo->ventas === 2 && (float) $efectivo->total === 140.0;
        });
});

test('acepta un rango personalizado', function () {
    s5Venta('2026-03-10', 200);
    s5Venta('2026-03-25', 300);
    s5Venta('2026-04-02', 999);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ventas', ['preset' => 'personalizado', 'desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->assertOk()
        ->assertViewHas('resumen', fn ($r) => (float) $r->total === 500.0);
});

test('un rango personalizado sin fechas es invalido', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ventas', ['preset' => 'personalizado']))
        ->assertSessionHasErrors(['desde', 'hasta']);
});

test('el detalle por día trae la lista de ventas de cada día', function () {
    $a = s5Venta('2026-03-10', 100);
    $b = s5Venta('2026-03-10', 40);
    $c = s5Venta('2026-03-11', 30);

    $this->actingAs($this->admin)
        ->get(route('admin.reportes.ventas', ['preset' => 'personalizado', 'desde' => '2026-03-01', 'hasta' => '2026-03-31']))
        ->assertOk()
        ->assertViewHas('porDia', function ($porDia) use ($a, $b) {
            $dia = $porDia->firstWhere('dia', '2026-03-10');

            return $dia['ventas'] === 2
                && (float) $dia['total'] === 140.0
                && $dia['detalle']->pluck('id')->all() === [$a->id, $b->id];
        })
        ->assertSee($a->numero)
        ->assertSee($c->numero);
});
