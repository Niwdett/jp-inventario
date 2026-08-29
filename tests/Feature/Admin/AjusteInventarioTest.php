<?php

use App\Enums\TipoMovimiento;
use App\Models\AjusteInventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Inventario\AjustarInventario;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->variante = Variante::factory()
        ->for(Producto::factory())
        ->create(['stock' => 8, 'costo_promedio' => 5]);
});

test('un empleado no puede ajustar inventario', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get(route('admin.inventario.ajustes.index'))->assertForbidden();
    $this->actingAs($empleado)->post(route('admin.inventario.ajustes.store'), [])->assertForbidden();
});

test('ajustar hacia abajo fija el stock al conteo y registra el movimiento sin usuario', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.inventario.ajustes.store'), [
            'variante_id' => $this->variante->id,
            'cantidad_nueva' => 5,
            'motivo' => 'Conteo físico trimestral',
        ])
        ->assertRedirect(route('admin.inventario.ajustes.index'));

    $this->variante->refresh();
    expect($this->variante->stock)->toBe(5)
        ->and((float) $this->variante->costo_promedio)->toBe(5.0); // el costo no cambia

    $ajuste = AjusteInventario::sole();
    expect($ajuste->cantidad_anterior)->toBe(8)
        ->and($ajuste->cantidad_nueva)->toBe(5);

    $movimiento = MovimientoInventario::sole();
    expect($movimiento->tipo)->toBe(TipoMovimiento::Ajuste)
        ->and($movimiento->cantidad)->toBe(-3)
        ->and($movimiento->stock_resultante)->toBe(5)
        ->and($movimiento->usuario_id)->toBeNull(); // RN-15
});

test('ajustar hacia arriba genera un movimiento positivo', function () {
    $this->actingAs($this->admin)->post(route('admin.inventario.ajustes.store'), [
        'variante_id' => $this->variante->id,
        'cantidad_nueva' => 12,
    ]);

    expect(MovimientoInventario::sole()->cantidad)->toBe(4)
        ->and($this->variante->refresh()->stock)->toBe(12);
});

test('no se puede ajustar a la misma cantidad que el stock actual', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.inventario.ajustes.create'))
        ->post(route('admin.inventario.ajustes.store'), [
            'variante_id' => $this->variante->id,
            'cantidad_nueva' => 8,
        ])
        ->assertSessionHasErrors('cantidad_nueva');

    expect(AjusteInventario::count())->toBe(0);
});

test('el servicio rechaza un ajuste sin cambio de cantidad', function () {
    expect(fn () => app(AjustarInventario::class)
        ->ejecutar($this->variante, 8, null))
        ->toThrow(InvalidArgumentException::class);

    expect(AjusteInventario::count())->toBe(0)
        ->and(MovimientoInventario::count())->toBe(0);
});
