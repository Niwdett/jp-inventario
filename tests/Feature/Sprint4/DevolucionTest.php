<?php

use App\Enums\EstadoDevolucion;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\DevolucionInvalidaException;
use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Devoluciones\RegistrarDevolucion;
use App\Services\Ventas\RegistrarVenta;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->cliente = Cliente::factory()->create(['saldo_favor' => 0]);
    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 20,
        'costo_promedio' => '5.0000',
    ]);

    $this->venta = app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 4, 'precio_unitario' => '25.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        $this->cliente,
        $this->admin,
    );
    $this->venta->forceFill(['entregada_at' => now()])->save();
    $this->variante->refresh(); // stock 16 tras la venta
    $this->linea = $this->venta->lineas->first();
    $this->registrar = app(RegistrarDevolucion::class);
});

function devolver(array $lineas, EstadoDevolucion $estado = EstadoDevolucion::Validada): mixed
{
    return test()->registrar->ejecutar(test()->venta, $lineas, 'Defecto de fábrica', $estado, Carbon::now(), test()->admin);
}

it('una devolución validada genera saldo a favor por lo pagado', function () {
    devolver([['venta_linea_id' => $this->linea->id, 'cantidad' => 2, 'reintegra_inventario' => true]]);

    // pagó 25/unidad → 2 × 25 = 50
    expect((float) $this->cliente->refresh()->saldo_favor)->toBe(50.0);

    $devolucion = $this->venta->devoluciones()->sole();
    expect($devolucion->estado)->toBe(EstadoDevolucion::Validada)
        ->and((float) $devolucion->saldo_generado)->toBe(50.0);
});

it('reintegra el stock solo de las líneas marcadas', function () {
    devolver([['venta_linea_id' => $this->linea->id, 'cantidad' => 2, 'reintegra_inventario' => true]]);

    expect($this->variante->refresh()->stock)->toBe(18);

    $movimiento = MovimientoInventario::where('tipo', TipoMovimiento::Devolucion)->sole();
    expect($movimiento->cantidad)->toBe(2)
        ->and($movimiento->stock_resultante)->toBe(18);
});

it('no reintegra stock cuando la línea se da de baja por daño', function () {
    devolver([['venta_linea_id' => $this->linea->id, 'cantidad' => 1, 'reintegra_inventario' => false]]);

    expect($this->variante->refresh()->stock)->toBe(16)
        ->and(MovimientoInventario::where('tipo', TipoMovimiento::Devolucion)->count())->toBe(0)
        ->and((float) $this->cliente->refresh()->saldo_favor)->toBe(25.0);
});

it('una devolución rechazada se registra sin efectos', function () {
    devolver([['venta_linea_id' => $this->linea->id, 'cantidad' => 2, 'reintegra_inventario' => true]], EstadoDevolucion::Rechazada);

    expect((float) $this->cliente->refresh()->saldo_favor)->toBe(0.0)
        ->and($this->variante->refresh()->stock)->toBe(16)
        ->and($this->venta->devoluciones()->sole()->estado)->toBe(EstadoDevolucion::Rechazada);
});

it('no permite devolver más unidades de las vendidas, ni acumulando devoluciones', function () {
    devolver([['venta_linea_id' => $this->linea->id, 'cantidad' => 3, 'reintegra_inventario' => true]]);

    expect(fn () => devolver([['venta_linea_id' => $this->linea->id, 'cantidad' => 2, 'reintegra_inventario' => true]]))
        ->toThrow(DevolucionInvalidaException::class);

    // La segunda no dejó rastro.
    expect($this->venta->devoluciones()->count())->toBe(1);
});

it('no permite devolver una venta que no fue entregada', function () {
    $sinEntregar = app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 1, 'precio_unitario' => '10.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        $this->cliente,
        $this->admin,
    );

    expect(fn () => test()->registrar->ejecutar($sinEntregar, [['venta_linea_id' => $sinEntregar->lineas->first()->id, 'cantidad' => 1, 'reintegra_inventario' => true]], 'x', EstadoDevolucion::Validada, Carbon::now(), $this->admin))
        ->toThrow(DevolucionInvalidaException::class);
});

it('no valida una devolución de una venta sin cliente', function () {
    $contado = app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 1, 'precio_unitario' => '10.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        null,
        $this->admin,
    );
    $contado->forceFill(['entregada_at' => now()])->save();

    expect(fn () => test()->registrar->ejecutar($contado, [['venta_linea_id' => $contado->lineas->first()->id, 'cantidad' => 1, 'reintegra_inventario' => true]], 'x', EstadoDevolucion::Validada, Carbon::now(), $this->admin))
        ->toThrow(DevolucionInvalidaException::class);
});

it('un empleado no puede abrir el formulario de devolución', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)
        ->get(route('admin.devoluciones.create', $this->venta))
        ->assertForbidden();
});

it('el administrador registra una devolución por HTTP', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.devoluciones.store', $this->venta), [
            'motivo' => 'Talla equivocada',
            'estado' => 'validada',
            'fecha' => now()->toDateString(),
            'lineas' => [
                $this->linea->id => ['incluir' => '1', 'cantidad' => '1', 'reintegra_inventario' => '1'],
            ],
        ])
        ->assertRedirect(route('ventas.show', $this->venta));

    expect((float) $this->cliente->refresh()->saldo_favor)->toBe(25.0)
        ->and($this->variante->refresh()->stock)->toBe(17);
});

it('la validación HTTP exige al menos una línea marcada', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.devoluciones.create', $this->venta))
        ->post(route('admin.devoluciones.store', $this->venta), [
            'motivo' => 'Nada',
            'estado' => 'validada',
            'fecha' => now()->toDateString(),
            'lineas' => [
                $this->linea->id => ['incluir' => '0', 'cantidad' => '2'],
            ],
        ])
        ->assertSessionHasErrors('lineas');
});
