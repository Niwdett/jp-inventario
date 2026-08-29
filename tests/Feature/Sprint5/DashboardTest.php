<?php

use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Models\VentaLinea;
use Illuminate\Support\Carbon;

test('el empleado ve su resumen del dia y no los indicadores del administrador', function () {
    $empleado = User::factory()->empleado()->create();

    Venta::factory()->deUsuario($empleado)->create(['fecha_venta' => now(), 'total' => 120]);
    Venta::factory()->create(['fecha_venta' => now(), 'total' => 500]); // de otro usuario

    $this->actingAs($empleado)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('empleado', fn ($datos) => $datos['ventas_hoy'] === 1 && (float) $datos['total_hoy'] === 120.0)
        ->assertViewMissing('admin');
});

test('el administrador ve los indicadores del negocio', function () {
    $admin = User::factory()->administrador()->create();

    Venta::factory()->create(['fecha_venta' => now(), 'total' => 200]);
    Venta::factory()->anulada()->create(['fecha_venta' => now(), 'total' => 999]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('admin', function ($datos) {
            return $datos['ventas_hoy'] === 1
                && (float) $datos['total_hoy'] === 200.0
                && (float) $datos['total_mes'] === 200.0;
        });
});

test('la ganancia del mes suma el margen de las lineas de ventas confirmadas', function () {
    $admin = User::factory()->administrador()->create();

    $venta = Venta::factory()->create(['fecha_venta' => now()]);
    $variante = Variante::factory()->create(['costo_promedio' => '6.0000']);
    VentaLinea::factory()->for($venta)->create([
        'variante_id' => $variante->id,
        'cantidad' => 2,
        'precio_unitario' => 10,
        'costo_unitario_snapshot' => '6.0000',
        'importe_linea' => 20,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertViewHas('admin', fn ($datos) => (float) $datos['ganancia_mes'] === 8.0);   // 20 - 12
});

test('cuenta los clientes en mora y el credito por cobrar', function () {
    $admin = User::factory()->administrador()->create();
    $cliente = Cliente::factory()->create();

    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 300,
        'credito_saldo_pendiente' => 300,
        'fecha_venta' => Carbon::now()->subDays(Cliente::DIAS_MORA + 5),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertViewHas('admin', fn ($datos) => $datos['clientes_en_mora'] === 1
            && (float) $datos['credito_por_cobrar'] === 300.0);
});

test('el dashboard redirige a login si no hay sesion', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
