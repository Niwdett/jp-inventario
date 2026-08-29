<?php

use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Services\Inventario\AjustarInventario;
use App\Services\Inventario\RegistrarEntrada;

/**
 * Invariante del bloque B3b (fundamental para Sprint 3): `variantes.stock`
 * siempre coincide con el `stock_resultante` del último movimiento de esa
 * variante, y la suma firmada de todos los movimientos da el stock actual.
 */
test('el stock de la variante coincide siempre con el ledger', function () {
    $admin = User::factory()->administrador()->create();
    $variante = Variante::factory()->for(Producto::factory())->create(['stock' => 0, 'costo_promedio' => 0]);

    app(RegistrarEntrada::class)->ejecutar($variante, 10, '20000', now()->toDateString(), null, $admin);
    app(RegistrarEntrada::class)->ejecutar($variante->refresh(), 5, '26000', now()->toDateString(), null, $admin);
    app(AjustarInventario::class)->ejecutar($variante->refresh(), 12, 'Conteo físico');

    $variante->refresh();

    $ultimoMovimiento = $variante->movimientos()->latest('id')->first();
    $sumaFirmada = (int) $variante->movimientos()->sum('cantidad');

    expect($variante->stock)->toBe(12)
        ->and($ultimoMovimiento->stock_resultante)->toBe(12)
        ->and($ultimoMovimiento->stock_resultante)->toBe($variante->stock)
        ->and($sumaFirmada)->toBe(12)
        ->and($variante->movimientos()->count())->toBe(3);
});

test('cada movimiento guarda el stock resultante correcto en secuencia', function () {
    $admin = User::factory()->administrador()->create();
    $variante = Variante::factory()->for(Producto::factory())->create(['stock' => 0, 'costo_promedio' => 0]);

    app(RegistrarEntrada::class)->ejecutar($variante, 10, '20000', now()->toDateString(), null, $admin);
    app(AjustarInventario::class)->ejecutar($variante->refresh(), 7, null);

    $movimientos = $variante->movimientos()->orderBy('id')->get();

    expect($movimientos->pluck('cantidad')->all())->toBe([10, -3])
        ->and($movimientos->pluck('stock_resultante')->all())->toBe([10, 7]);
});
