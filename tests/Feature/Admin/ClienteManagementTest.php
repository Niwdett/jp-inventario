<?php

use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('un empleado no puede gestionar clientes', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get(route('admin.clientes.index'))->assertForbidden();
    $this->actingAs($empleado)->post(route('admin.clientes.store'), [])->assertForbidden();
});

test('el administrador ve la lista de clientes', function () {
    Cliente::factory()->create(['nombre' => 'Marta Ríos']);

    $this->actingAs($this->admin)
        ->get(route('admin.clientes.index'))
        ->assertOk()
        ->assertSee('Marta Ríos');
});

test('el administrador registra un cliente', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.clientes.store'), [
            'nombre' => '  Luis Pérez ',
            'telefono' => '3001234567',
            'cedula' => '1088123456',
        ])
        ->assertRedirect();

    $cliente = Cliente::firstWhere('cedula', '1088123456');
    expect($cliente->nombre)->toBe('Luis Pérez')
        ->and((float) $cliente->saldo_favor)->toBe(0.0);
});

test('el nombre es obligatorio', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.clientes.create'))
        ->post(route('admin.clientes.store'), ['nombre' => ''])
        ->assertSessionHasErrors('nombre');
});

test('la cédula no se repite entre clientes activos pero sí tras eliminar', function () {
    $cliente = Cliente::factory()->create(['cedula' => '999']);

    $this->actingAs($this->admin)
        ->post(route('admin.clientes.store'), ['nombre' => 'Otro', 'cedula' => '999'])
        ->assertSessionHasErrors('cedula');

    $cliente->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.clientes.store'), ['nombre' => 'Reusa', 'cedula' => '999'])
        ->assertSessionHasNoErrors();

    expect(Cliente::where('cedula', '999')->count())->toBe(1);
});

test('la cédula es opcional', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.clientes.store'), ['nombre' => 'Sin cédula'])
        ->assertSessionHasNoErrors();

    Cliente::factory()->create();
    $this->actingAs($this->admin)
        ->post(route('admin.clientes.store'), ['nombre' => 'Tampoco'])
        ->assertSessionHasNoErrors();
});

test('el administrador actualiza un cliente', function () {
    $cliente = Cliente::factory()->create(['nombre' => 'Antiguo']);

    $this->actingAs($this->admin)
        ->put(route('admin.clientes.update', $cliente), ['nombre' => 'Nuevo', 'telefono' => '311'])
        ->assertRedirect(route('admin.clientes.show', $cliente));

    expect($cliente->refresh()->nombre)->toBe('Nuevo');
});

test('la ficha muestra el saldo a favor y las ventas a crédito abiertas', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 40]);
    Venta::factory()->for($cliente)->create([
        'numero' => 'V-000123',
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 60,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.clientes.show', $cliente))
        ->assertOk()
        ->assertSee('40.00')
        ->assertSee('V-000123')
        ->assertSee('60.00');
});

test('no se puede eliminar un cliente con saldo a favor', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 15]);

    $this->actingAs($this->admin)
        ->delete(route('admin.clientes.destroy', $cliente))
        ->assertSessionHas('error');

    expect($cliente->refresh()->trashed())->toBeFalse();
});

test('no se puede eliminar un cliente con crédito pendiente', function () {
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.clientes.destroy', $cliente))
        ->assertSessionHas('error');

    expect($cliente->refresh()->trashed())->toBeFalse();
});

test('se elimina y se restaura un cliente sin dinero pendiente', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 0]);

    $this->actingAs($this->admin)->delete(route('admin.clientes.destroy', $cliente));
    expect($cliente->refresh()->trashed())->toBeTrue();

    $this->actingAs($this->admin)->patch(route('admin.clientes.restore', $cliente));
    expect($cliente->refresh()->trashed())->toBeFalse();
});
