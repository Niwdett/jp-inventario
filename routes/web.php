<?php

use App\Http\Controllers\AjusteInventarioController;
use App\Http\Controllers\AlertaStockController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\EntradaInventarioController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VarianteController;
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
