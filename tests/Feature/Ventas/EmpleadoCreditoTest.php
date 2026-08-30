<?php

use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;

/**
 * Decisión revisada (2026-08-29): el Empleado puede registrar abonos, pero solo
 * de las ventas a crédito que él mismo registró (RN-08). La cartera de crédito
 * (`creditos.index`) sigue siendo exclusiva del Administrador.
 */
beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->otroVendedor = User::factory()->empleado()->create();

    $this->ventaPropia = Venta::factory()->deUsuario($this->vendedor)->for(Cliente::factory())->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
    ]);
});

it('el empleado registra un abono de su propia venta a crédito', function () {
    $this->actingAs($this->vendedor)
        ->post(route('admin.creditos.abonos.store', $this->ventaPropia), [
            'monto' => '30.00',
            'fecha' => now()->toDateString(),
        ])
        ->assertRedirect(route('ventas.show', $this->ventaPropia));

    expect((float) $this->ventaPropia->refresh()->credito_saldo_pendiente)->toBe(70.0)
        ->and($this->ventaPropia->abonos()->sole()->usuario_id)->toBe($this->vendedor->id);
});

it('el empleado no puede abonar la venta a crédito de otro vendedor', function () {
    $ventaAjena = Venta::factory()->deUsuario($this->otroVendedor)->for(Cliente::factory())->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
    ]);

    $this->actingAs($this->vendedor)
        ->post(route('admin.creditos.abonos.store', $ventaAjena), [
            'monto' => '30.00',
            'fecha' => now()->toDateString(),
        ])
        ->assertForbidden();

    expect($ventaAjena->refresh()->abonos()->count())->toBe(0);
});

it('el empleado no puede abonar una venta que no es a crédito', function () {
    $contado = Venta::factory()->deUsuario($this->vendedor)->create(['metodo_pago' => MetodoPago::Efectivo]);

    $this->actingAs($this->vendedor)
        ->post(route('admin.creditos.abonos.store', $contado), ['monto' => '10.00', 'fecha' => now()->toDateString()])
        ->assertForbidden();
});

it('el empleado no puede abonar una deuda ya saldada', function () {
    $this->ventaPropia->forceFill(['credito_saldo_pendiente' => 0])->save();

    $this->actingAs($this->vendedor)
        ->post(route('admin.creditos.abonos.store', $this->ventaPropia), ['monto' => '1.00', 'fecha' => now()->toDateString()])
        ->assertForbidden();
});

it('el empleado sigue sin ver la cartera de crédito', function () {
    $this->actingAs($this->vendedor)
        ->get(route('admin.creditos.index'))
        ->assertForbidden();
});

it('el formulario de abono aparece en la ficha de la venta propia del empleado', function () {
    $this->actingAs($this->vendedor)
        ->get(route('ventas.show', $this->ventaPropia))
        ->assertOk()
        ->assertSee('Registrar abono');
});

it('la pantalla de venta ofrece al empleado registrar un cliente nuevo', function () {
    $this->actingAs($this->vendedor)
        ->get(route('ventas.create'))
        ->assertOk()
        ->assertSee('Registrar cliente');
});
