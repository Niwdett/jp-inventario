<?php

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
});

test('un empleado no puede gestionar usuarios', function () {
    $empleado = User::factory()->empleado()->create();

    $this->actingAs($empleado)->get(route('admin.usuarios.index'))->assertForbidden();
    $this->actingAs($empleado)->post(route('admin.usuarios.store'), [])->assertForbidden();
});

test('el administrador ve la lista de usuarios', function () {
    $otro = User::factory()->create(['name' => 'Pepita Pérez']);

    $this->actingAs($this->admin)
        ->get(route('admin.usuarios.index'))
        ->assertOk()
        ->assertSee('Pepita Pérez');
});

test('el administrador crea un usuario', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.usuarios.store'), [
            'name' => 'Nuevo Vendedor',
            'email' => 'Vendedor@JP.CO',
            'rol' => Rol::Empleado->value,
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
        ])
        ->assertRedirect(route('admin.usuarios.index'));

    $user = User::where('email', 'vendedor@jp.co')->first();

    expect($user)->not->toBeNull()
        ->and($user->rol)->toBe(Rol::Empleado)
        ->and(Hash::check('clave-segura', $user->password))->toBeTrue();
});

test('la creación valida los datos', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.create'))
        ->post(route('admin.usuarios.store'), [
            'name' => '',
            'email' => 'no-es-correo',
            'rol' => 'superusuario',
            'password' => 'x',
            'password_confirmation' => 'y',
        ])
        ->assertRedirect(route('admin.usuarios.create'))
        ->assertSessionHasErrors(['name', 'email', 'rol', 'password']);
});

test('no se puede repetir el correo de otro usuario', function () {
    User::factory()->create(['email' => 'ocupado@jp.co']);

    $this->actingAs($this->admin)
        ->post(route('admin.usuarios.store'), [
            'name' => 'Colisión',
            'email' => 'ocupado@jp.co',
            'rol' => Rol::Empleado->value,
            'password' => 'clave-segura',
            'password_confirmation' => 'clave-segura',
        ])
        ->assertSessionHasErrors('email');
});

test('el administrador edita nombre, correo y rol', function () {
    $user = User::factory()->empleado()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.usuarios.update', $user), [
            'name' => 'Nombre Cambiado',
            'email' => 'cambiado@jp.co',
            'rol' => Rol::Administrador->value,
        ])
        ->assertRedirect(route('admin.usuarios.index'));

    $user->refresh();

    expect($user->name)->toBe('Nombre Cambiado')
        ->and($user->email)->toBe('cambiado@jp.co')
        ->and($user->rol)->toBe(Rol::Administrador);
});

test('editar sin contraseña conserva la actual; con contraseña la cambia', function () {
    $user = User::factory()->empleado()->create();
    $hashOriginal = $user->password;

    $this->actingAs($this->admin)->put(route('admin.usuarios.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'rol' => $user->rol->value,
    ]);
    expect($user->refresh()->password)->toBe($hashOriginal);

    $this->actingAs($this->admin)->put(route('admin.usuarios.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'rol' => $user->rol->value,
        'password' => 'nueva-clave-larga',
        'password_confirmation' => 'nueva-clave-larga',
    ]);
    expect(Hash::check('nueva-clave-larga', $user->refresh()->password))->toBeTrue();
});

test('no se puede degradar al último administrador', function () {
    // $this->admin es el único administrador
    $this->actingAs($this->admin)
        ->from(route('admin.usuarios.edit', $this->admin))
        ->put(route('admin.usuarios.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'rol' => Rol::Empleado->value,
        ])
        ->assertSessionHasErrors('rol');

    expect($this->admin->refresh()->rol)->toBe(Rol::Administrador);
});

test('el administrador desactiva a otro usuario y este ya no puede entrar', function () {
    $user = User::factory()->empleado()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.usuarios.destroy', $user))
        ->assertRedirect(route('admin.usuarios.index'));

    expect($user->refresh()->trashed())->toBeTrue()
        ->and(auth()->validate(['email' => $user->email, 'password' => 'password']))->toBeFalse();
});

test('el administrador no puede desactivarse a sí mismo', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.usuarios.destroy', $this->admin))
        ->assertSessionHas('error');

    expect($this->admin->refresh()->trashed())->toBeFalse();
});

test('se puede desactivar a otro administrador mientras quede al menos uno', function () {
    $otroAdmin = User::factory()->administrador()->create();

    $this->actingAs($this->admin)->delete(route('admin.usuarios.destroy', $otroAdmin));

    expect($otroAdmin->refresh()->trashed())->toBeTrue()
        ->and($this->admin->refresh()->trashed())->toBeFalse();
});

test('el administrador reactiva a un usuario desactivado', function () {
    $user = User::factory()->empleado()->create();
    $user->delete();

    $this->actingAs($this->admin)
        ->patch(route('admin.usuarios.restore', $user))
        ->assertRedirect(route('admin.usuarios.index'));

    expect($user->refresh()->trashed())->toBeFalse();
});
