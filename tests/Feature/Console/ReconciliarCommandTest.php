<?php

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Inventario\RegistrarEntrada;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->variante = Variante::factory()->for(Producto::factory())->create(['stock' => 0, 'costo_promedio' => 0]);
    app(RegistrarEntrada::class)->ejecutar($this->variante, 10, '5', now()->toDateString(), null, $this->admin);
});

test('sin discrepancias termina con éxito', function () {
    $this->artisan('jp:reconciliar')
        ->expectsOutputToContain('Todo cuadra')
        ->assertSuccessful();
});

test('detecta un stock que no cuadra con el ledger y falla', function () {
    DB::table('variantes')->where('id', $this->variante->id)->update(['stock' => 999]);

    $this->artisan('jp:reconciliar')
        ->expectsOutputToContain('Inventario')
        ->assertFailed();
});

test('--fix corrige el stock desde el ledger', function () {
    DB::table('variantes')->where('id', $this->variante->id)->update(['stock' => 999]);

    $this->artisan('jp:reconciliar --fix')->assertSuccessful();

    expect($this->variante->refresh()->stock)->toBe(10);
});

test('detecta y corrige un saldo a favor que no cuadra con su libro', function () {
    $cliente = Cliente::factory()->create();
    DB::table('clientes')->where('id', $cliente->id)->update(['saldo_favor' => '150.00']);

    $this->artisan('jp:reconciliar')
        ->expectsOutputToContain('Saldo a favor')
        ->assertFailed();

    $this->artisan('jp:reconciliar --fix')->assertSuccessful();

    expect((float) $cliente->refresh()->saldo_favor)->toBe(0.0);
});

test('detecta y corrige un crédito pendiente que no cuadra con los abonos', function () {
    $venta = Venta::factory()->create([
        'metodo_pago' => 'credito',
        'credito_monto' => '100.00',
        'credito_saldo_pendiente' => '100.00',
    ]);
    $venta->abonos()->create(['monto' => '40.00', 'fecha' => now()->toDateString(), 'usuario_id' => $this->admin->id]);
    // El abono no bajó el pendiente (simula un descuadre).

    $this->artisan('jp:reconciliar')
        ->expectsOutputToContain('Crédito pendiente')
        ->assertFailed();

    $this->artisan('jp:reconciliar --fix')->assertSuccessful();

    expect((float) $venta->refresh()->credito_saldo_pendiente)->toBe(60.0);
});

test('una venta anulada debe tener el crédito pendiente en cero', function () {
    $venta = Venta::factory()->anulada()->create([
        'metodo_pago' => 'credito',
        'credito_monto' => '100.00',
        'credito_saldo_pendiente' => '100.00',   // debería ser 0 tras anular
    ]);

    $this->artisan('jp:reconciliar')->assertFailed();

    $this->artisan('jp:reconciliar --fix')->assertSuccessful();

    expect((float) $venta->refresh()->credito_saldo_pendiente)->toBe(0.0);
});
