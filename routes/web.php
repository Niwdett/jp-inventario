<?php

use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\AlertaStockController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\EntradaInventarioController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VarianteController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

/*
 * Ventas (RF-008, RF-009, RF-010). Empleado y Administrador; la VentaPolicy
 * restringe al Empleado a sus propias ventas (RN-08, decisiones G1–G3).
 */
Route::middleware(['auth', 'rol:administrador,empleado'])
    ->prefix('ventas')
    ->name('ventas.')
    ->group(function () {
        Route::get('/', [VentaController::class, 'index'])->name('index');
        Route::get('/registrar', [VentaController::class, 'create'])->name('create');
        Route::post('/', [VentaController::class, 'store'])->name('store');
        Route::get('/{venta}', [VentaController::class, 'show'])->name('show');
        Route::patch('/{venta}/anular', [VentaController::class, 'anular'])->name('anular');
        Route::patch('/{venta}/entregar', [VentaController::class, 'entregar'])->name('entregar');
    });

/*
 * Módulos administrativos. Requieren sesión y rol Administrador (RF-001, RF-002).
 */
Route::middleware(['auth', 'rol:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except('show');
        Route::patch('usuarios/{usuario}/restaurar', [UsuarioController::class, 'restore'])
            ->withTrashed()
            ->name('usuarios.restore');

        Route::resource('categorias', CategoriaController::class)->except('show');
        Route::patch('categorias/{categoria}/restaurar', [CategoriaController::class, 'restore'])
            ->withTrashed()
            ->name('categorias.restore');

        Route::resource('clientes', ClienteController::class);
        Route::patch('clientes/{cliente}/restaurar', [ClienteController::class, 'restore'])
            ->withTrashed()
            ->name('clientes.restore');

        // Créditos y abonos (RF-014).
        Route::get('creditos', [CreditoController::class, 'index'])->name('creditos.index');
        Route::post('creditos/{venta}/abonos', [CreditoController::class, 'abonar'])->name('creditos.abonos.store');

        // Devoluciones tras la entrega (RF-011).
        Route::get('devoluciones', [DevolucionController::class, 'index'])->name('devoluciones.index');
        Route::get('ventas/{venta}/devoluciones/registrar', [DevolucionController::class, 'create'])->name('devoluciones.create');
        Route::post('ventas/{venta}/devoluciones', [DevolucionController::class, 'store'])->name('devoluciones.store');

        Route::resource('productos', ProductoController::class);
        Route::patch('productos/{producto}/restaurar', [ProductoController::class, 'restore'])
            ->withTrashed()
            ->name('productos.restore');
        Route::resource('productos.variantes', VarianteController::class)
            ->only(['store', 'edit', 'update', 'destroy']);

        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('entradas', [EntradaInventarioController::class, 'index'])->name('entradas.index');
            Route::get('entradas/registrar', [EntradaInventarioController::class, 'create'])->name('entradas.create');
            Route::post('entradas', [EntradaInventarioController::class, 'store'])->name('entradas.store');

            Route::get('ajustes', [AjusteInventarioController::class, 'index'])->name('ajustes.index');
            Route::get('ajustes/registrar', [AjusteInventarioController::class, 'create'])->name('ajustes.create');
            Route::post('ajustes', [AjusteInventarioController::class, 'store'])->name('ajustes.store');

            Route::get('movimientos', [MovimientoInventarioController::class, 'index'])->name('movimientos.index');

            Route::get('alertas', [AlertaStockController::class, 'index'])->name('alertas.index');
        });
    });

require __DIR__.'/auth.php';
