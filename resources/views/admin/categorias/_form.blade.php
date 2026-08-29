@props(['categoria' => null])

@php($esEdicion = ! is_null($categoria))

<div>
    <x-input-label for="nombre" :value="__('Nombre')" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
        :value="old('nombre', $categoria?->nombre)" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="prefijo_codigo" :value="__('Prefijo de código')" />
    <x-text-input id="prefijo_codigo" name="prefijo_codigo" type="text"
        class="mt-1 block w-full uppercase" maxlength="10"
        :value="old('prefijo_codigo', $categoria?->prefijo_codigo)" required />
    <p class="mt-1 text-sm text-gray-500">
        {{ __('Solo letras. Es el prefijo del código interno de los productos de esta categoría (p. ej. CAL → CAL-0001).') }}
    </p>
    <x-input-error :messages="$errors->get('prefijo_codigo')" class="mt-2" />
</div>

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $esEdicion ? __('Guardar cambios') : __('Crear categoría') }}</x-primary-button>
    <a href="{{ route('admin.categorias.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
        {{ __('Cancelar') }}
    </a>
</div>
