<?php

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\TipoSaldoFavor;
use App\Exceptions\ClienteEnMoraException;
use App\Exceptions\PagoVentaInvalidoException;
use App\Exceptions\SaldoFavorInsuficienteException;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Ventas\AnularVenta;
use App\Services\Ventas\RegistrarVenta;

beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->admin = User::factory()->administrador()->create();
    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 50,
        'costo_promedio' => '5.0000',
    ]);
    $this->registrar = app(RegistrarVenta::class);
});

function linea(Variante $v, int $cantidad, string $precio): array
{
    return ['variante_id' => $v->id, 'cantidad' => $cantidad, 'precio_unitario' => $precio, 'descuento_porcentaje' => null];
}

it('registra una venta a crédito y crea la deuda por venta', function () {
    $cliente = Cliente::factory()->create();

    $venta = $this->registrar->ejecutar(
        [linea($this->variante, 2, '50.00')],
        MetodoPago::Credito,
        $cliente,
        $this->vendedor,
    );

    expect($venta->metodo_pago)->toBe(MetodoPago::Credito)
        ->and((float) $venta->credito_monto)->toBe(100.0)
        ->and((float) $venta->credito_saldo_pendiente)->toBe(100.0)
        ->and($venta->credito_autorizado_por)->toBeNull();
});

it('exige cliente para una venta a crédito', function () {
    expect(fn () => $this->registrar->ejecutar([linea($this->variante, 1, '10.00')], MetodoPago::Credito, null, $this->vendedor))
        ->toThrow(PagoVentaInvalidoException::class);

    expect(Venta::count())->toBe(0);
});

it('aplica saldo a favor y cobra el resto por el método elegido', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 30]);

    $venta = $this->registrar->ejecutar(
        [linea($this->variante, 1, '100.00')],
        MetodoPago::Efectivo,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '30.00',
    );

    expect((float) $venta->total)->toBe(100.0)
        ->and((float) $venta->saldo_favor_aplicado)->toBe(30.0)
        ->and($venta->metodo_pago)->toBe(MetodoPago::Efectivo)
        ->and((float) $cliente->refresh()->saldo_favor)->toBe(0.0);

    $movimiento = $cliente->saldoFavorMovimientos()->sole();
    expect($movimiento->tipo)->toBe(TipoSaldoFavor::Aplicado)
        ->and((float) $movimiento->monto)->toBe(-30.0);
});

it('cuando el saldo a favor cubre el total, la venta queda como efectivo sin deuda', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 200]);

    $venta = $this->registrar->ejecutar(
        [linea($this->variante, 1, '80.00')],
        MetodoPago::Credito,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '80.00',
    );

    expect($venta->metodo_pago)->toBe(MetodoPago::Efectivo)
        ->and($venta->credito_monto)->toBeNull()
        ->and((float) $cliente->refresh()->saldo_favor)->toBe(120.0);
});

it('combina saldo a favor con crédito: el restante es la deuda', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 40]);

    $venta = $this->registrar->ejecutar(
        [linea($this->variante, 1, '100.00')],
        MetodoPago::Credito,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '40.00',
    );

    expect((float) $venta->credito_monto)->toBe(60.0)
        ->and((float) $venta->credito_saldo_pendiente)->toBe(60.0)
        ->and((float) $cliente->refresh()->saldo_favor)->toBe(0.0);
});

it('no permite aplicar más saldo del disponible', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 10]);

    expect(fn () => $this->registrar->ejecutar(
        [linea($this->variante, 1, '100.00')],
        MetodoPago::Efectivo,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '20.00',
    ))->toThrow(SaldoFavorInsuficienteException::class);

    expect(Venta::count())->toBe(0)
        ->and((float) $cliente->refresh()->saldo_favor)->toBe(10.0);
});

it('no permite aplicar más saldo que el total de la venta', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 500]);

    expect(fn () => $this->registrar->ejecutar(
        [linea($this->variante, 1, '30.00')],
        MetodoPago::Efectivo,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '31.00',
    ))->toThrow(PagoVentaInvalidoException::class);
});

it('bloquea al empleado que vende a crédito a un cliente en mora (RN-09)', function () {
    $cliente = clienteEnMora();

    expect(fn () => $this->registrar->ejecutar([linea($this->variante, 1, '10.00')], MetodoPago::Credito, $cliente, $this->vendedor))
        ->toThrow(ClienteEnMoraException::class);

    expect(Venta::where('metodo_pago', MetodoPago::Credito)->where('credito_saldo_pendiente', '>', 0)->count())->toBe(1);
});

it('el administrador no puede vender a crédito en mora sin autorizar explícitamente', function () {
    $cliente = clienteEnMora();

    expect(fn () => $this->registrar->ejecutar([linea($this->variante, 1, '10.00')], MetodoPago::Credito, $cliente, $this->admin))
        ->toThrow(ClienteEnMoraException::class);
});

it('el administrador autoriza la venta a crédito en mora y queda registrado', function () {
    $cliente = clienteEnMora();

    $venta = $this->registrar->ejecutar(
        [linea($this->variante, 1, '25.00')],
        MetodoPago::Credito,
        $cliente,
        $this->admin,
        autorizarMora: true,
    );

    expect($venta->credito_autorizado_por)->toBe($this->admin->id);
});

it('al anular una venta a crédito con abonos, devuelve lo abonado como saldo a favor y cancela la deuda', function () {
    $cliente = Cliente::factory()->create();

    $venta = $this->registrar->ejecutar([linea($this->variante, 2, '50.00')], MetodoPago::Credito, $cliente, $this->vendedor);
    $venta->abonos()->create(['monto' => '30.00', 'fecha' => now()->toDateString(), 'usuario_id' => $this->admin->id]);
    $venta->forceFill(['credito_saldo_pendiente' => '70.00'])->save();

    app(AnularVenta::class)->ejecutar($venta, 'Cliente se arrepintió', $this->admin);

    expect($venta->refresh()->estado)->toBe(EstadoVenta::Anulada)
        ->and((float) $venta->credito_saldo_pendiente)->toBe(0.0)
        ->and((float) $cliente->refresh()->saldo_favor)->toBe(30.0)
        ->and($this->variante->refresh()->stock)->toBe(50);
});

it('al anular una venta que aplicó saldo a favor, lo reintegra', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 40]);

    $venta = $this->registrar->ejecutar(
        [linea($this->variante, 1, '100.00')],
        MetodoPago::Efectivo,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '40.00',
    );
    expect((float) $cliente->refresh()->saldo_favor)->toBe(0.0);

    app(AnularVenta::class)->ejecutar($venta, 'Anulada', $this->admin);

    expect((float) $cliente->refresh()->saldo_favor)->toBe(40.0);
});

function clienteEnMora(): Cliente
{
    $cliente = Cliente::factory()->create();
    Venta::factory()->for($cliente)->create([
        'metodo_pago' => MetodoPago::Credito,
        'credito_monto' => 100,
        'credito_saldo_pendiente' => 100,
        'fecha_venta' => now()->subDays(20),
    ]);

    return $cliente;
}
