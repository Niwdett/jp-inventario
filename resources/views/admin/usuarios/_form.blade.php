@props(['usuario' => null, 'roles' => []])

@php($esEdicion = ! is_null($usuario))

<div class="space-y-5">
    <div>
        <x-input-label for="name" :value="__('Nombre')" />
        <x-text-input id="name" name="name" type="text" class="mt-1.5"
            :value="old('name', $usuario?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Correo')" />
        <x-text-input id="email" name="email" type="email" class="mt-1.5"
            :value="old('email', $usuario?->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="rol" :value="__('Rol')" />
        <x-select-input id="rol" name="rol" class="mt-1.5"
            :options="$roles" :selected="$usuario?->rol?->value"
            :placeholder="$esEdicion ? null : __('— Selecciona un rol —')" required />
        <x-input-error :messages="$errors->get('rol')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="password" :value="$esEdicion ? __('Nueva contraseña') : __('Contraseña')" />
        <x-text-input id="password" name="password" type="password" class="mt-1.5"
            autocomplete="new-password" :required="! $esEdicion" />
        @if ($esEdicion)
            <p class="mt-1.5 text-xs text-ink-faint">{{ __('Déjala en blanco para conservar la contraseña actual.') }}</p>
        @endif
        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
            class="mt-1.5" autocomplete="new-password" :required="! $esEdicion" />
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <x-button>{{ $esEdicion ? __('Guardar cambios') : __('Crear usuario') }}</x-button>
    <x-button variant="ghost" :href="route('admin.usuarios.index')">{{ __('Cancelar') }}</x-button>
</div>
