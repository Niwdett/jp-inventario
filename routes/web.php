<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
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
    });

require __DIR__.'/auth.php';
