<?php

use App\Enums\TipoSaldoFavor;
use App\Exceptions\SaldoFavorInsuficienteException;
use App\Models\Cliente;
use App\Models\Venta;
use App\Services\Clientes\MovimientoSaldoFavor;
use Illuminate\Support\Facades\DB;

/**
 * Helper que respeta el contrato del servicio: transacción abierta + cliente
 * bloqueado antes de mover el saldo.
 */
function moverSaldo(Cliente $cliente, callable $accion): mixed
{
    return DB::transaction(function () use ($cliente, $accion) {
        $bloqueado = Cliente::whereKey($cliente->id)->lockForUpdate()->first();

        return $accion(app(MovimientoSaldoFavor::class), $bloqueado);
    });
}

it('genera saldo a favor: suma al cacheado y registra el movimiento', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 10]);
    $referencia = Venta::factory()->create();

    moverSaldo($cliente, fn (MovimientoSaldoFavor $s, Cliente $c) => $s->generar($c, '25.50', $referencia));

    expect((float) $cliente->refresh()->saldo_favor)->toBe(35.50);

    $movimiento = $cliente->saldoFavorMovimientos()->sole();
    expect($movimiento->tipo)->toBe(TipoSaldoFavor::Generado)
        ->and((float) $movimiento->monto)->toBe(25.50)
        ->and($movimiento->referencia->is($referencia))->toBeTrue();
});

it('aplica saldo a favor: resta del cacheado y registra el movimiento en negativo', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 100]);
    $referencia = Venta::factory()->create();

    moverSaldo($cliente, fn (MovimientoSaldoFavor $s, Cliente $c) => $s->aplicar($c, '30.00', $referencia));

    expect((float) $cliente->refresh()->saldo_favor)->toBe(70.0);

    $movimiento = $cliente->saldoFavorMovimientos()->sole();
    expect($movimiento->tipo)->toBe(TipoSaldoFavor::Aplicado)
        ->and((float) $movimiento->monto)->toBe(-30.0);
});

it('no permite aplicar más saldo del disponible y no deja rastro', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 20]);
    $referencia = Venta::factory()->create();

    expect(fn () => moverSaldo($cliente, fn (MovimientoSaldoFavor $s, Cliente $c) => $s->aplicar($c, '20.01', $referencia)))
        ->toThrow(SaldoFavorInsuficienteException::class);

    expect((float) $cliente->refresh()->saldo_favor)->toBe(20.0)
        ->and($cliente->saldoFavorMovimientos()->count())->toBe(0);
});

it('permite aplicar exactamente todo el saldo disponible', function () {
    $cliente = Cliente::factory()->create(['saldo_favor' => 45]);
    $referencia = Venta::factory()->create();

    moverSaldo($cliente, fn (MovimientoSaldoFavor $s, Cliente $c) => $s->aplicar($c, '45.00', $referencia));

    expect((float) $cliente->refresh()->saldo_favor)->toBe(0.0);
});
