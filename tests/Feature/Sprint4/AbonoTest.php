<?php

use App\Enums\MetodoPago;
use App\Exceptions\AbonoInvalidoException;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use App\Services\Creditos\RegistrarAbono;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->venta = Venta::factory()->for(Cliente::factory())->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
    ]);
    $this->registrar = app(RegistrarAbono::class);
});

it('registra un abono parcial y baja el saldo pendiente', function () {
    $this->registrar->ejecutar($this->venta, '40.00', Carbon::parse('2026-08-20'), $this->admin);

    expect((float) $this->venta->refresh()->credito_saldo_pendiente)->toBe(60.0)
        ->and($this->venta->abonos()->count())->toBe(1)
        ->and($this->venta->abonos()->first()->usuario_id)->toBe($this->admin->id);
});

it('salda la deuda cuando el abono cubre el pendiente', function () {
    $this->registrar->ejecutar($this->venta, '100.00', now(), $this->admin);

    expect((float) $this->venta->refresh()->credito_saldo_pendiente)->toBe(0.0);
});

it('rechaza el sobrepago y no deja rastro', function () {
    expect(fn () => $this->registrar->ejecutar($this->venta, '100.01', now(), $this->admin))
        ->toThrow(AbonoInvalidoException::class);

    expect((float) $this->venta->refresh()->credito_saldo_pendiente)->toBe(100.0)
        ->and($this->venta->abonos()->count())->toBe(0);
});

it('rechaza un abono sobre una venta que no es a crédito', function () {
    $contado = Venta::factory()->create(['metodo_pago' => MetodoPago::Efectivo]);

    expect(fn () => $this->registrar->ejecutar($contado, '10.00', now(), $this->admin))
        ->toThrow(AbonoInvalidoException::class);
});

it('un empleado no puede registrar abonos por HTTP', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->post(route('admin.creditos.abonos.store', $this->venta), ['monto' => '10', 'fecha' => now()->toDateString()])
        ->assertForbidden();
});

it('el administrador registra un abono por HTTP y vuelve a la venta', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.creditos.abonos.store', $this->venta), ['monto' => '25.50', 'fecha' => now()->toDateString()])
        ->assertRedirect(route('ventas.show', $this->venta));

    expect((float) $this->venta->refresh()->credito_saldo_pendiente)->toBe(74.5);
});

it('la validación HTTP rechaza monto no positivo y fecha futura', function () {
    $this->actingAs($this->admin)
        ->from(route('ventas.show', $this->venta))
        ->post(route('admin.creditos.abonos.store', $this->venta), ['monto' => '0', 'fecha' => now()->addDay()->toDateString()])
        ->assertSessionHasErrors(['monto', 'fecha']);
});

it('el listado de créditos muestra las ventas con saldo pendiente', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.creditos.index'))
        ->assertOk()
        ->assertSee($this->venta->numero);
});
