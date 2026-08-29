<?php

use App\Enums\TipoMovimiento;
use App\Models\EntradaInventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->producto = Producto::factory()->create(['umbral_stock_bajo' => 2]);
    $this->variante = Variante::factory()->for($this->producto)->create([
        'stock' => 10,
        'costo_promedio' => 5,
    ]);
});

test('un empleado no puede registrar entradas', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get(route('admin.inventario.entradas.index'))->assertForbidden();
    $this->actingAs($empleado)->post(route('admin.inventario.entradas.store'), [])->assertForbidden();
});

test('registrar una entrada sube el stock y recalcula el costo promedio ponderado', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.inventario.entradas.store'), [
            'variante_id' => $this->variante->id,
            'cantidad' => 10,
            'costo_unitario' => '7',
            'fecha' => now()->toDateString(),
            'proveedor' => 'Distribuidora X',
        ])
        ->assertRedirect(route('admin.inventario.entradas.index'));

    $this->variante->refresh();

    // (10 * 5 + 10 * 7) / 20 = 6
    expect($this->variante->stock)->toBe(20)
        ->and((float) $this->variante->costo_promedio)->toBe(6.0);

    $entrada = EntradaInventario::sole();
    expect($entrada->usuario_id)->toBe($this->admin->id)
        ->and($entrada->cantidad)->toBe(10);

    $movimiento = MovimientoInventario::sole();
    expect($movimiento->tipo)->toBe(TipoMovimiento::Entrada)
        ->and($movimiento->cantidad)->toBe(10)
        ->and($movimiento->stock_resultante)->toBe(20)
        ->and($movimiento->usuario_id)->toBe($this->admin->id)
        ->and($movimiento->referencia->is($entrada))->toBeTrue();
});

test('la primera entrada sobre una variante en cero fija el costo al costo de compra', function () {
    $vacia = Variante::factory()->for($this->producto)->create(['stock' => 0, 'costo_promedio' => 0, 'talla' => 'XL', 'color' => 'Gris']);

    $this->actingAs($this->admin)->post(route('admin.inventario.entradas.store'), [
        'variante_id' => $vacia->id,
        'cantidad' => 4,
        'costo_unitario' => '12.5',
        'fecha' => now()->toDateString(),
    ]);

    $vacia->refresh();
    expect($vacia->stock)->toBe(4)
        ->and((float) $vacia->costo_promedio)->toBe(12.5);
});

test('la entrada valida los datos', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.inventario.entradas.create'))
        ->post(route('admin.inventario.entradas.store'), [
            'variante_id' => 999,
            'cantidad' => 0,
            'costo_unitario' => 'gratis',
            'fecha' => now()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors(['variante_id', 'cantidad', 'costo_unitario', 'fecha']);
});
