<?php

use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\EntradaNoAnulableException;
use App\Exceptions\StockNegativoAlAnularEntradaException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Inventario\AnularEntrada;
use App\Services\Inventario\RegistrarEntrada;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->vendedor = User::factory()->empleado()->create();
    $this->producto = Producto::factory()->create(['umbral_stock_bajo' => 2]);
    $this->variante = Variante::factory()->for($this->producto)->create(['stock' => 0, 'costo_promedio' => 0]);

    $this->registrarEntrada = app(RegistrarEntrada::class);
    $this->anularEntrada = app(AnularEntrada::class);
});

test('un empleado no puede anular una entrada', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '5', now()->toDateString(), null, $this->admin);

    $this->actingAs($this->vendedor)
        ->patch(route('admin.inventario.entradas.anular', $entrada), ['motivo' => 'prueba de acceso'])
        ->assertForbidden();

    expect($entrada->refresh()->esAnulable())->toBeTrue();
});

test('anular una entrada reconstruye stock y costo y deja el movimiento compensatorio', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '5', now()->toDateString(), null, $this->admin);

    $resultado = $this->anularEntrada->ejecutar($entrada, 'Costo mal tecleado', $this->admin);

    $this->variante->refresh();
    expect($this->variante->stock)->toBe(0)
        ->and((float) $this->variante->costo_promedio)->toBe(0.0);

    $entrada->refresh();
    expect($entrada->esAnulable())->toBeFalse()
        ->and($entrada->anulada_por)->toBe($this->admin->id)
        ->and($entrada->motivo_anulacion)->toBe('Costo mal tecleado')
        ->and($entrada->anulada_at)->not->toBeNull();

    $compensatorio = MovimientoInventario::where('tipo', TipoMovimiento::AnulacionEntrada)->sole();
    expect($compensatorio->cantidad)->toBe(-10)
        ->and($compensatorio->stock_resultante)->toBe(0)
        ->and($compensatorio->usuario_id)->toBe($this->admin->id)
        ->and($compensatorio->referencia->is($entrada))->toBeTrue();

    // La entrada original NO se borra y su movimiento sigue en el libro.
    expect(MovimientoInventario::where('tipo', TipoMovimiento::Entrada)->count())->toBe(1);

    expect($resultado->mensaje())->toContain('5.0000 → 0.0000')
        ->and($resultado->mensaje())->toContain('10 → 0');
});

test('la reconstruccion reproduce el ledger completo, no solo la entrada anulada', function () {
    // A: 10 @ 10  → costo 10, stock 10
    $a = $this->registrarEntrada->ejecutar($this->variante, 10, '10', now()->toDateString(), null, $this->admin);
    // B: 10 @ 20  → costo (100+200)/20 = 15, stock 20
    $this->registrarEntrada->ejecutar($this->variante->refresh(), 10, '20', now()->toDateString(), null, $this->admin);

    expect((float) $this->variante->refresh()->costo_promedio)->toBe(15.0);

    // Al anular A, B queda como única entrada: costo 20, stock 10.
    $this->anularEntrada->ejecutar($a, 'Proveedor equivocado', $this->admin);

    $this->variante->refresh();
    expect($this->variante->stock)->toBe(10)
        ->and((float) $this->variante->costo_promedio)->toBe(20.0);
});

test('una entrada posterior no impide anular la anterior', function () {
    $a = $this->registrarEntrada->ejecutar($this->variante, 5, '10', now()->toDateString(), null, $this->admin);
    $this->registrarEntrada->ejecutar($this->variante->refresh(), 5, '10', now()->toDateString(), null, $this->admin);

    $this->anularEntrada->ejecutar($a, 'Duplicada', $this->admin);

    $this->variante->refresh();
    expect($this->variante->stock)->toBe(5)
        ->and((float) $this->variante->costo_promedio)->toBe(10.0);
});

test('no se puede anular dos veces la misma entrada', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '5', now()->toDateString(), null, $this->admin);
    $this->anularEntrada->ejecutar($entrada, 'primera', $this->admin);

    expect(fn () => $this->anularEntrada->ejecutar($entrada->refresh(), 'segunda', $this->admin))
        ->toThrow(EntradaNoAnulableException::class);

    expect(MovimientoInventario::where('tipo', TipoMovimiento::AnulacionEntrada)->count())->toBe(1);
});

test('se rechaza la anulacion si el stock quedaria negativo', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 5, '10', now()->toDateString(), null, $this->admin);

    // Se venden las 5 unidades: ya no existen para "devolver" al anular la entrada.
    app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 5, 'precio_unitario' => '15.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );

    expect(fn () => $this->anularEntrada->ejecutar($entrada, 'no debe pasar', $this->admin))
        ->toThrow(StockNegativoAlAnularEntradaException::class, 'corto en 5 unidades');

    // Nada cambió: entrada vigente, sin movimiento compensatorio.
    expect($entrada->refresh()->esAnulable())->toBeTrue()
        ->and($this->variante->refresh()->stock)->toBe(0)
        ->and(MovimientoInventario::where('tipo', TipoMovimiento::AnulacionEntrada)->count())->toBe(0);
});

test('el deficit reportado es acumulado a lo largo de varias ventas', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '10', now()->toDateString(), null, $this->admin);

    foreach ([4, 3, 3] as $cantidad) {
        app(RegistrarVenta::class)->ejecutar(
            [['variante_id' => $this->variante->id, 'cantidad' => $cantidad, 'precio_unitario' => '15.00', 'descuento_porcentaje' => null]],
            MetodoPago::Efectivo,
            null,
            $this->vendedor,
        );
    }

    expect(fn () => $this->anularEntrada->ejecutar($entrada, 'no debe pasar', $this->admin))
        ->toThrow(StockNegativoAlAnularEntradaException::class, 'corto en 10 unidades');
});

test('anular una entrada no toca el costo snapshot de las ventas ya hechas', function () {
    $a = $this->registrarEntrada->ejecutar($this->variante, 10, '10', now()->toDateString(), null, $this->admin);
    $this->registrarEntrada->ejecutar($this->variante->refresh(), 10, '20', now()->toDateString(), null, $this->admin);

    // Venta con el costo promedio del momento (15.0000).
    $venta = app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 2, 'precio_unitario' => '30.00', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        null,
        $this->vendedor,
    );
    $snapshotAntes = $venta->lineas()->sole()->costo_unitario_snapshot;

    $this->anularEntrada->ejecutar($a, 'Costo inflado', $this->admin);

    expect($venta->lineas()->sole()->costo_unitario_snapshot)->toBe($snapshotAntes)
        ->and((float) $this->variante->refresh()->costo_promedio)->not->toBe(15.0); // el costo EN VIVO sí cambió
});

test('la valoracion de inventario refleja el costo reconstruido', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '45', now()->toDateString(), null, $this->admin);

    $this->actingAs($this->admin)->get(route('admin.reportes.inventario'))
        ->assertViewHas('valorTotal', '450.00');

    $this->anularEntrada->ejecutar($entrada, 'Un cero de más', $this->admin);
    $this->registrarEntrada->ejecutar($this->variante->refresh(), 10, '4.5', now()->toDateString(), null, $this->admin);

    $this->actingAs($this->admin)->get(route('admin.reportes.inventario'))
        ->assertViewHas('valorTotal', '45.00');
});

test('la ruta HTTP anula y muestra el efecto concreto', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '5', now()->toDateString(), null, $this->admin);

    $this->actingAs($this->admin)
        ->from(route('admin.inventario.entradas.index'))
        ->patch(route('admin.inventario.entradas.anular', $entrada), ['motivo' => 'Costo mal tecleado'])
        ->assertRedirect(route('admin.inventario.entradas.index'))
        ->assertSessionHas('status', fn ($m) => str_contains($m, '5.0000 → 0.0000'));

    expect($entrada->refresh()->esAnulable())->toBeFalse();
});

test('la ruta HTTP valida el motivo', function () {
    $entrada = $this->registrarEntrada->ejecutar($this->variante, 10, '5', now()->toDateString(), null, $this->admin);

    $this->actingAs($this->admin)
        ->from(route('admin.inventario.entradas.index'))
        ->patch(route('admin.inventario.entradas.anular', $entrada), ['motivo' => 'x'])
        ->assertSessionHasErrors('motivo');

    expect($entrada->refresh()->esAnulable())->toBeTrue();
});
