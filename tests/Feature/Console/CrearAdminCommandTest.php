<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('crea un administrador con opciones (modo no interactivo)', function () {
    $this->artisan('jp:crear-admin', [
        '--name' => 'Ana Gerente',
        '--email' => 'Ana@JP.CO',
        '--password' => 'clave-segura',
    ])->assertSuccessful();

    $user = User::where('email', 'ana@jp.co')->first();

    expect($user)->not->toBeNull()
        ->and($user->rol)->toBe(Rol::Administrador)
        ->and(Hash::check('clave-segura', $user->password))->toBeTrue();
});

test('crea un administrador de forma interactiva', function () {
    $this->artisan('jp:crear-admin')
        ->expectsQuestion('Nombre del administrador', 'Ana Gerente')
        ->expectsQuestion('Correo', 'ana@jp.co')
        ->expectsQuestion('Contraseña (mínimo 8 caracteres)', 'clave-segura')
        ->assertSuccessful();

    expect(User::where('email', 'ana@jp.co')->where('rol', Rol::Administrador)->exists())->toBeTrue();
});

test('falla si el correo ya existe', function () {
    User::factory()->create(['email' => 'ocupado@jp.co']);

    $this->artisan('jp:crear-admin', [
        '--name' => 'Otro',
        '--email' => 'ocupado@jp.co',
        '--password' => 'clave-segura',
    ])->assertFailed();
});

test('falla si la contraseña es muy corta', function () {
    $this->artisan('jp:crear-admin', [
        '--name' => 'Otro',
        '--email' => 'nuevo@jp.co',
        '--password' => 'corta',
    ])->assertFailed();

    expect(User::where('email', 'nuevo@jp.co')->exists())->toBeFalse();
});
