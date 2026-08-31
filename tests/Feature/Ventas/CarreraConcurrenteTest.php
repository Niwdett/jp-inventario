<?php

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\TipoMovimiento;
use App\Exceptions\SaldoFavorInsuficienteException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Inventario\MovimientoStock;
use App\Services\Ventas\AnularVenta;
use App\Services\Ventas\RegistrarVenta;
use Illuminate\Support\Facades\DB;

/**
 * Guardas ante operaciones en paralelo.
 *
 * Una prueba con dos procesos reales necesitaría un arnés externo; aquí la
 * "carrera" se simula ejecutando la operación en conflicto ANTES —lo que deja el
 * mismo estado obsoleto que vería la segunda transacción— y se verifica que la
 * guarda correspondiente (política, recheck bajo lock, UPDATE condicional) la
 * rechaza sin corromper datos.
 */
beforeEach(function () {
    $this->vendedor = User::factory()->empleado()->create();
    $this->otro = User::factory()->empleado()->create();

    $this->variante = Variante::factory()->for(Producto::factory())->create([
        'stock' => 5,
        'costo_promedio' => '2.0000',
    ]);
});

function ventaEfectivo(Variante $variante, int $cantidad, User $usuario): Venta
{
    return app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $variante->id, 'cantidad' => $cantidad, 'precio_unitario' => '10', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        null,
        $usuario,
    );
}

// --- entrega vs. anulación (cierre de E-2) ---

test('no se puede marcar entregada una venta que ya fue anulada', function () {
    $venta = ventaEfectivo($this->variante, 1, $this->vendedor);
    app(AnularVenta::class)->ejecutar($venta, 'se arrepintió', $this->vendedor);

    // La política (`puedeEntregarse`) y, tras ella, el recheck bajo lock del
    // controlador, la bloquean.
    $this->actingAs($this->vendedor)
        ->patch(route('ventas.entregar', $venta))
        ->assertForbidden();

    expect($venta->refresh()->entregada_at)->toBeNull()
        ->and($venta->estado)->toBe(EstadoVenta::Anulada);
});

test('no se puede anular una venta que ya fue entregada', function () {
    $venta = ventaEfectivo($this->variante, 1, $this->vendedor);
    $venta->forceFill(['entregada_at' => now()])->save();

    $this->actingAs($this->vendedor)
        ->patch(route('ventas.anular', $venta), ['motivo' => 'ya no'])
        ->assertForbidden();

    expect($venta->refresh()->estado)->toBe(EstadoVenta::Confirmada)
        ->and($venta->anulada_at)->toBeNull()
        ->and($this->variante->refresh()->stock)->toBe(4);   // el stock no se reintegró
});

test('un empleado no puede marcar entregada la venta de otro empleado', function () {
    $venta = ventaEfectivo($this->variante, 1, $this->vendedor);

    $this->actingAs($this->otro)
        ->patch(route('ventas.entregar', $venta))
        ->assertForbidden();

    expect($venta->refresh()->entregada_at)->toBeNull();
});

// --- stock ---

test('una venta no consume stock que otra operación agotó en paralelo', function () {
    // Otra venta / ajuste simultáneo deja la variante en 0.
    DB::table('variantes')->where('id', $this->variante->id)->update(['stock' => 0]);

    expect(fn () => ventaEfectivo($this->variante, 1, $this->vendedor))
        ->toThrow(StockInsuficienteException::class);

    expect(Venta::count())->toBe(0)
        ->and($this->variante->refresh()->stock)->toBe(0);   // no quedó negativo
});

test('el UPDATE condicional frena un descuento basado en una lectura obsoleta del stock', function () {
    $venta = Venta::factory()->create();
    $copiaObsoleta = Variante::find($this->variante->id);                       // en memoria: 5

    DB::table('variantes')->where('id', $this->variante->id)->update(['stock' => 0]);   // real: 0

    expect(fn () => DB::transaction(fn () => app(MovimientoStock::class)
        ->descontar($copiaObsoleta, 3, TipoMovimiento::Venta, $venta, $this->vendedor)))
        ->toThrow(StockInsuficienteException::class);

    expect($this->variante->refresh()->stock)->toBe(0)                          // no -3
        ->and($venta->movimientos()->count())->toBe(0);
});

// --- saldo a favor ---

test('una venta no aplica saldo a favor que otra venta consumió en paralelo', function () {
    $cliente = Cliente::factory()->create();
    DB::table('clientes')->where('id', $cliente->id)->update(['saldo_favor' => '30.00']);

    // Otra venta del mismo cliente consumió su saldo a favor entre medias.
    DB::table('clientes')->where('id', $cliente->id)->update(['saldo_favor' => '0.00']);

    // RegistrarVenta vuelve a leer al cliente bajo `lockForUpdate` -> ve saldo 0.
    expect(fn () => app(RegistrarVenta::class)->ejecutar(
        [['variante_id' => $this->variante->id, 'cantidad' => 1, 'precio_unitario' => '10', 'descuento_porcentaje' => null]],
        MetodoPago::Efectivo,
        $cliente,
        $this->vendedor,
        saldoFavorAplicado: '10.00',
    ))->toThrow(SaldoFavorInsuficienteException::class);

    expect(Venta::count())->toBe(0)
        ->and($cliente->saldoFavorMovimientos()->count())->toBe(0)
        ->and($this->variante->refresh()->stock)->toBe(5);   // el stock no se tocó
});
