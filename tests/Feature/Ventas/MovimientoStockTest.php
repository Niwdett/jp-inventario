<?php

use App\Enums\TipoMovimiento;
use App\Exceptions\StockInsuficienteException;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->helper = app(MovimientoStock::class);
    $this->variante = Variante::factory()->for(Producto::factory())->create(['stock' => 5]);
    $this->venta = Venta::factory()->create();
    $this->usuario = User::factory()->create();
});

test('descontar aplica el UPDATE condicional y registra el movimiento', function () {
    DB::transaction(function () {
        $mov = $this->helper->descontar($this->variante, 3, TipoMovimiento::Venta, $this->venta, $this->usuario);

        expect($mov->cantidad)->toBe(-3)
            ->and($mov->stock_resultante)->toBe(2);
    });

    expect($this->variante->refresh()->stock)->toBe(2);
});

test('descontar lanza StockInsuficienteException cuando el stock no alcanza y no toca nada', function () {
    expect(fn () => DB::transaction(fn () => $this->helper->descontar($this->variante, 9, TipoMovimiento::Venta, $this->venta, $this->usuario)))
        ->toThrow(StockInsuficienteException::class);

    expect($this->variante->refresh()->stock)->toBe(5)
        ->and($this->venta->movimientos()->count())->toBe(0);
});

test('la guarda de no-negatividad frena un segundo descuento con stock ya agotado', function () {
    DB::transaction(function () {
        $this->helper->descontar($this->variante, 5, TipoMovimiento::Venta, $this->venta, $this->usuario);

        expect(fn () => $this->helper->descontar($this->variante, 1, TipoMovimiento::Venta, $this->venta, $this->usuario))
            ->toThrow(StockInsuficienteException::class);
    });

    expect($this->variante->refresh()->stock)->toBe(0);
});

test('reintegrar suma al stock sin guarda', function () {
    DB::transaction(fn () => $this->helper->reintegrar($this->variante, 4, TipoMovimiento::Anulacion, $this->venta, $this->usuario));

    expect($this->variante->refresh()->stock)->toBe(9);
});
