<?php

use App\Enums\EstadoDevolucion;
use App\Enums\TipoSaldoFavor;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\DevolucionLinea;
use App\Models\SaldoFavorMovimiento;
use App\Models\Venta;
use App\Models\VentaLinea;

/**
 * Parte 1 del Sprint 4: sólo el esqueleto (tablas, modelos, relaciones y
 * factories). La lógica de negocio se cubre en los tests de las Partes 3–6.
 */
it('persiste un movimiento de saldo a favor ligado a su cliente', function () {
    $movimiento = SaldoFavorMovimiento::factory()->create();

    expect($movimiento->tipo)->toBe(TipoSaldoFavor::Generado)
        ->and($movimiento->cliente)->toBeInstanceOf(Cliente::class);

    $aplicado = SaldoFavorMovimiento::factory()->aplicado()->create();
    expect($aplicado->tipo)->toBe(TipoSaldoFavor::Aplicado)
        ->and((float) $aplicado->monto)->toBeLessThan(0);
});

it('relaciona cliente con su libro de saldo a favor', function () {
    $cliente = Cliente::factory()->create();
    SaldoFavorMovimiento::factory()->count(2)->for($cliente)->create();

    expect($cliente->saldoFavorMovimientos)->toHaveCount(2);
});

it('persiste un abono ligado a su venta y a quien lo registró', function () {
    $abono = Abono::factory()->create();

    expect($abono->venta)->toBeInstanceOf(Venta::class)
        ->and($abono->usuario->esAdministrador())->toBeTrue()
        ->and($abono->venta->abonos)->toHaveCount(1);
});

it('persiste una devolución con sus líneas y expone sus movimientos', function () {
    $devolucion = Devolucion::factory()->create();
    DevolucionLinea::factory()->count(2)->for($devolucion)->create();

    expect($devolucion->estado)->toBe(EstadoDevolucion::Validada)
        ->and($devolucion->lineas)->toHaveCount(2)
        ->and($devolucion->venta->devoluciones)->toHaveCount(1)
        ->and($devolucion->movimientos()->count())->toBe(0);

    $rechazada = Devolucion::factory()->rechazada()->create();
    expect($rechazada->estado)->toBe(EstadoDevolucion::Rechazada);
});

it('cuenta cero unidades devueltas cuando la línea no tiene devoluciones', function () {
    $linea = VentaLinea::factory()->create(['cantidad' => 3]);

    expect($linea->cantidadDevuelta())->toBe(0)
        ->and($linea->valorUnitarioPagado())->toBe(bcdiv((string) $linea->importe_linea, '3', 2));
});

it('sólo cuenta como devueltas las unidades de devoluciones validadas', function () {
    $linea = VentaLinea::factory()->create(['cantidad' => 5]);

    DevolucionLinea::factory()->for($linea, 'ventaLinea')
        ->for(Devolucion::factory()->rechazada())->create(['cantidad' => 2]);
    expect($linea->cantidadDevuelta())->toBe(0);

    DevolucionLinea::factory()->for($linea, 'ventaLinea')
        ->for(Devolucion::factory())->create(['cantidad' => 2]);
    expect($linea->cantidadDevuelta())->toBe(2);
});
