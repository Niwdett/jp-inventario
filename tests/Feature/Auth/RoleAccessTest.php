<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('los invitados son redirigidos al login en rutas administrativas', function () {
    $this->get('/admin/usuarios')->assertRedirect('/login');
});

test('un empleado no puede entrar a un módulo solo de administrador', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get('/admin/usuarios')->assertForbidden();
});

test('un administrador sí puede entrar a un módulo solo de administrador', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)->get('/admin/usuarios')->assertOk();
});

test('el middleware rol acepta varios roles separados por coma', function () {
    config()->set('app.debug', false);

    $empleado = User::factory()->empleado()->create();

    // Ruta ad-hoc con rol:administrador,empleado
    Route::get('/_test/mixta', fn () => 'ok')
        ->middleware(['auth', 'rol:administrador,empleado']);

    $this->actingAs($empleado)->get('/_test/mixta')->assertOk()->assertSee('ok');
});
