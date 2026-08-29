@props(['cliente' => null])

@php($esEdicion = ! is_null($cliente))

<div>
    <x-input-label for="nombre" :value="__('Nombre')" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
        :value="old('nombre', $cliente?->nombre)" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="telefono" :value="__('Teléfono')" />
    <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
        :value="old('telefono', $cliente?->telefono)" />
    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="cedula" :value="__('Cédula')" />
    <x-text-input id="cedula" name="cedula" type="text" class="mt-1 block w-full"
        :value="old('cedula', $cliente?->cedula)" />
    <p class="mt-1 text-sm text-gray-500">
        {{ __('Opcional. Si se registra, no puede repetirse entre clientes activos.') }}
    </p>
    <x-input-error :messages="$errors->get('cedula')" class="mt-2" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $esEdicion ? __('Guardar cambios') : __('Registrar cliente') }}</x-primary-button>
    <a href="{{ $esEdicion ? route('admin.clientes.show', $cliente) : route('admin.clientes.index') }}"
       class="text-sm text-gray-600 hover:text-gray-900 underline">
        {{ __('Cancelar') }}
    </a>
</div>
