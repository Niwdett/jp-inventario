<?php

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->producto = Producto::factory()->create();
    $this->variante = Variante::factory()->for($this->producto)->create([
        'stock' => 10,
        'costo_promedio' => '8.5000',
    ]);
    $this->registrar = app(RegistrarVenta::class);
});

function lineaVenta(Variante $variante, int $cantidad, string $precio, ?string $descuento = null): array
{
    return [
        'variante_id' => $variante->id,
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
        'descuento_porcentaje' => $descuento,
    ];
}

test('confirmar una venta descuenta el stock y registra el movimiento', function () {
    $venta = $this->registrar->ejecutar(
        [lineaVenta($this->variante, 3, '20.00')],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );

    expect($venta->estado)->toBe(EstadoVenta::Confirmada)
        ->and($venta->usuario_id)->toBe($this->vendedor->id)
        ->and($venta->metodo_pago)->toBe(MetodoPago::Efectivo);

    expect($this->variante->refresh()->stock)->toBe(7);

    $movimiento = MovimientoInventario::sole();
    expect($movimiento->tipo)->toBe(TipoMovimiento::Venta)
        ->and($movimiento->cantidad)->toBe(-3)
        ->and($movimiento->stock_resultante)->toBe(7)
        ->and($movimiento->usuario_id)->toBe($this->vendedor->id)
        ->and($movimiento->referencia->is($venta))->toBeTrue();
});

test('la línea guarda el snapshot del costo promedio vigente', function () {
    $venta = $this->registrar->ejecutar(
        [lineaVenta($this->variante, 1, '20.00')],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );

    expect((float) $venta->lineas->first()->costo_unitario_snapshot)->toBe(8.5);

    // Un cambio posterior del costo no toca la línea ya vendida (A2, RN-05).
    $this->variante->forceFill(['costo_promedio' => '99.0000'])->save();
    expect((float) $venta->lineas->first()->fresh()->costo_unitario_snapshot)->toBe(8.5);
});

test('una variante con costo promedio 0 se puede vender y guarda snapshot 0', function () {
    $sinCosto = Variante::factory()->for($this->producto)->create([
        'stock' => 5,
        'costo_promedio' => '0.0000',
        'talla' => 'L',
        'color' => 'Verde',
    ]);

    $venta = $this->registrar->ejecutar(
        [lineaVenta($sinCosto, 2, '15.00')],
        MetodoPago::Transferencia,
        null,
        $this->vendedor,
    );

    expect((float) $venta->lineas->first()->costo_unitario_snapshot)->toBe(0.0);
});

test('el número de venta es correlativo y con formato V-000001', function () {
    $primera = $this->registrar->ejecutar([lineaVenta($this->variante, 1, '10.00')], MetodoPago::Efectivo, null, $this->vendedor);
    $segunda = $this->registrar->ejecutar([lineaVenta($this->variante, 1, '10.00')], MetodoPago::Efectivo, null, $this->vendedor);

    expect($primera->numero)->toBe('V-000001')
        ->and($segunda->numero)->toBe('V-000002');
});

test('calcula subtotal, descuento total y total con aritmética decimal', function () {
    $otra = Variante::factory()->for($this->producto)->create(['stock' => 5, 'costo_promedio' => '3', 'talla' => 'M', 'color' => 'Azul']);

    $venta = $this->registrar->ejecutar([
        lineaVenta($this->variante, 2, '20.00', '10'),   // 40 - 10% = 36.00
        lineaVenta($otra, 1, '15.50'),                    // 15.50
    ], MetodoPago::Efectivo, null, $this->vendedor);

    expect((float) $venta->subtotal)->toBe(55.5)
        ->and((float) $venta->total)->toBe(51.5)
        ->and((float) $venta->descuento_total)->toBe(4.0);

    expect((float) $venta->lineas->firstWhere('variante_id', $this->variante->id)->importe_linea)->toBe(36.0);
});

test('rechaza la venta si una línea pide más stock del disponible y no deja rastro', function () {
    expect(fn () => $this->registrar->ejecutar(
        [lineaVenta($this->variante, 11, '20.00')],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    ))->toThrow(StockInsuficienteException::class);

    expect($this->variante->refresh()->stock)->toBe(10)
        ->and(Venta::count())->toBe(0)
        ->and(MovimientoInventario::count())->toBe(0);
});

test('no reutiliza el número de una venta tras un intento fallido', function () {
    $this->registrar->ejecutar([lineaVenta($this->variante, 1, '10.00')], MetodoPago::Efectivo, null, $this->vendedor);

    try {
        $this->registrar->ejecutar([lineaVenta($this->variante, 999, '10.00')], MetodoPago::Efectivo, null, $this->vendedor);
    } catch (StockInsuficienteException) {
        // esperado
    }

    $tercera = $this->registrar->ejecutar([lineaVenta($this->variante, 1, '10.00')], MetodoPago::Efectivo, null, $this->vendedor);

    // El ROLLBACK del intento fallido revierte el incremento de la secuencia:
    // no hay hueco, pero tampoco se reutiliza un número ya confirmado.
    expect($tercera->numero)->toBe('V-000002');
});
