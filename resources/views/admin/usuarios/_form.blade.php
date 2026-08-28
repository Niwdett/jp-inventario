@props(['usuario' => null, 'roles' => []])

@php($esEdicion = ! is_null($usuario))

<div>
    <x-input-label for="name" :value="__('Nombre')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
        :value="old('name', $usuario?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="email" :value="__('Correo')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
        :value="old('email', $usuario?->email)" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="rol" :value="__('Rol')" />
    <x-select-input id="rol" name="rol" class="mt-1 block w-full"
        :options="$roles" :selected="$usuario?->rol?->value"
        :placeholder="$esEdicion ? null : __('— Selecciona un rol —')" required />
    <x-input-error :messages="$errors->get('rol')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="password"
        :value="$esEdicion ? __('Nueva contraseña') : __('Contraseña')" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
        autocomplete="new-password" :required="! $esEdicion" />
    @if ($esEdicion)
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Déjala en blanco para conservar la contraseña actual.') }}
        </p>
    @endif
    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
        class="mt-1 block w-full" autocomplete="new-password" :required="! $esEdicion" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $esEdicion ? __('Guardar cambios') : __('Crear usuario') }}</x-primary-button>
    <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
        {{ __('Cancelar') }}
    </a>
</div>
