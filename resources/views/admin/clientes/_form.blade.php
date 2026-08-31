@props(['cliente' => null])

@php($esEdicion = ! is_null($cliente))

<div class="space-y-5">
    <div>
        <x-input-label for="nombre" :value="__('Nombre')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5"
            :value="old('nombre', $cliente?->nombre)" required autofocus />
        <x-input-error :messages="$errors->get('nombre')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="telefono" :value="__('Teléfono')" />
        <x-text-input id="telefono" name="telefono" type="text" class="mt-1.5"
            :value="old('telefono', $cliente?->telefono)" />
        <x-input-error :messages="$errors->get('telefono')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="cedula" :value="__('Cédula')" />
        <x-text-input id="cedula" name="cedula" type="text" class="mt-1.5"
            :value="old('cedula', $cliente?->cedula)" />
        <p class="mt-1.5 text-xs text-ink-faint">
            {{ __('Opcional. Si se registra, no puede repetirse entre clientes activos.') }}
        </p>
        <x-input-error :messages="$errors->get('cedula')" class="mt-1.5" />
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <x-button>{{ $esEdicion ? __('Guardar cambios') : __('Registrar cliente') }}</x-button>
    <x-button variant="ghost" :href="$esEdicion ? route('admin.clientes.show', $cliente) : route('admin.clientes.index')">
        {{ __('Cancelar') }}
    </x-button>
</div>
