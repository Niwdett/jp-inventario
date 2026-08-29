<?php

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\VentaNoAnulableException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Ventas\AnularVenta;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->admin = User::factory()->administrador()->create();
    $this->producto = Producto::factory()->create();
    $this->variante = Variante::factory()->for($this->producto)->create([
        'stock' => 10,
        'costo_promedio' => '8.0000',
    ]);

    $this->venta = app(RegistrarVenta::class)->ejecutar(
        [[
            'variante_id' => $this->variante->id,
            'cantidad' => 4,
            'precio_unitario' => '20.00',
            'descuento_porcentaje' => null,
        ]],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );

    $this->anular = app(AnularVenta::class);
});

test('anular una venta reintegra el stock y registra el movimiento de anulación', function () {
    expect($this->variante->refresh()->stock)->toBe(6);

    $venta = $this->anular->ejecutar($this->venta, 'Cliente se arrepintió', $this->admin);

    expect($venta->estado)->toBe(EstadoVenta::Anulada)
        ->and($venta->anulada_por)->toBe($this->admin->id)
        ->and($venta->motivo_anulacion)->toBe('Cliente se arrepintió')
        ->and($venta->anulada_at)->not->toBeNull();

    expect($this->variante->refresh()->stock)->toBe(10);

    $movimiento = MovimientoInventario::where('tipo', TipoMovimiento::Anulacion)->sole();
    expect($movimiento->cantidad)->toBe(4)
        ->and($movimiento->stock_resultante)->toBe(10)
        ->and($movimiento->usuario_id)->toBe($this->admin->id)
        ->and($movimiento->referencia->is($venta))->toBeTrue();
});

test('no se puede anular una venta ya entregada', function () {
    $this->venta->forceFill(['entregada_at' => now()])->save();

    expect(fn () => $this->anular->ejecutar($this->venta, 'motivo', $this->admin))
        ->toThrow(VentaNoAnulableException::class);

    expect($this->variante->refresh()->stock)->toBe(6)
        ->and($this->venta->refresh()->estado)->toBe(EstadoVenta::Confirmada);
});

test('no se puede anular dos veces la misma venta', function () {
    $this->anular->ejecutar($this->venta, 'primera', $this->admin);

    expect(fn () => $this->anular->ejecutar($this->venta->refresh(), 'segunda', $this->admin))
        ->toThrow(VentaNoAnulableException::class);

    expect($this->variante->refresh()->stock)->toBe(10)
        ->and(MovimientoInventario::where('tipo', TipoMovimiento::Anulacion)->count())->toBe(1);
});

test('el invariante stock == último movimiento se mantiene tras venta y anulación', function () {
    $this->anular->ejecutar($this->venta, 'motivo', $this->admin);

    $ultimo = MovimientoInventario::where('variante_id', $this->variante->id)->latest('id')->first();
    expect($this->variante->refresh()->stock)->toBe($ultimo->stock_resultante);
});
