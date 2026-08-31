@props(['categoria' => null])

@php($esEdicion = ! is_null($categoria))

<div class="space-y-5">
    <div>
        <x-input-label for="nombre" :value="__('Nombre')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5"
            :value="old('nombre', $categoria?->nombre)" required autofocus />
        <x-input-error :messages="$errors->get('nombre')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="prefijo_codigo" :value="__('Prefijo de código')" />
        <x-text-input id="prefijo_codigo" name="prefijo_codigo" type="text"
            class="mt-1.5 uppercase" maxlength="10"
            :value="old('prefijo_codigo', $categoria?->prefijo_codigo)" required />
        <p class="mt-1.5 text-xs text-ink-faint">
            {{ __('Solo letras. Es el prefijo del código interno de los productos de esta categoría (p. ej. CAL → CAL-0001).') }}
        </p>
        <x-input-error :messages="$errors->get('prefijo_codigo')" class="mt-1.5" />
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <x-button>{{ $esEdicion ? __('Guardar cambios') : __('Crear categoría') }}</x-button>
    <x-button variant="ghost" :href="route('admin.categorias.index')">{{ __('Cancelar') }}</x-button>
</div>
