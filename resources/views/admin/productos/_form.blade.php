@props(['producto' => null, 'categorias' => null])

@php($esEdicion = ! is_null($producto))

<div>
    <x-input-label for="categoria_id" :value="__('Categoría')" />
    @if ($esEdicion)
        {{-- La categoría no cambia tras el alta: el código interno quedaría inconsistente con el prefijo. --}}
        <p class="mt-1 text-gray-800">{{ $producto->categoria->nombre }}
            <span class="text-gray-400 text-sm">({{ __('no editable') }})</span>
        </p>
    @else
        <x-select-input id="categoria_id" name="categoria_id" class="mt-1 block w-full"
            :options="$categorias->pluck('nombre', 'id')"
            :selected="old('categoria_id')"
            :placeholder="__('— Selecciona una categoría —')" required />
        <x-input-error :messages="$errors->get('categoria_id')" class="mt-2" />
    @endif
</div>

<div class="mt-4">
    <x-input-label for="nombre" :value="__('Nombre')" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
        :value="old('nombre', $producto?->nombre)" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="marca" :value="__('Marca (opcional)')" />
    <x-text-input id="marca" name="marca" type="text" class="mt-1 block w-full"
        :value="old('marca', $producto?->marca)" />
    <x-input-error :messages="$errors->get('marca')" class="mt-2" />
</div>

<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="precio_referencia" :value="__('Precio de referencia')" />
        <x-text-input id="precio_referencia" name="precio_referencia" type="number" step="0.01" min="0"
            class="mt-1 block w-full" :value="old('precio_referencia', $producto?->precio_referencia)" required />
        <x-input-error :messages="$errors->get('precio_referencia')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="umbral_stock_bajo" :value="__('Umbral de stock bajo')" />
        <x-text-input id="umbral_stock_bajo" name="umbral_stock_bajo" type="number" min="0"
            class="mt-1 block w-full" :value="old('umbral_stock_bajo', $producto?->umbral_stock_bajo ?? 0)" required />
        <p class="mt-1 text-sm text-gray-500">{{ __('Se avisa cuando una variante llega a esta cantidad o menos.') }}</p>
        <x-input-error :messages="$errors->get('umbral_stock_bajo')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="proveedor" :value="__('Proveedor (opcional)')" />
    <x-text-input id="proveedor" name="proveedor" type="text" class="mt-1 block w-full"
        :value="old('proveedor', $producto?->proveedor)" />
    <x-input-error :messages="$errors->get('proveedor')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="foto" :value="__('Foto (opcional)')" />
    @if ($esEdicion && $producto->foto)
        <img src="{{ Storage::url($producto->foto) }}" alt="" class="mt-1 h-24 w-24 object-cover rounded-md border border-gray-200">
    @endif
    <input id="foto" name="foto" type="file" accept="image/*"
        class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold hover:file:bg-gray-200">
    <x-input-error :messages="$errors->get('foto')" class="mt-2" />
</div>

@unless ($esEdicion)
    <fieldset class="mt-6 border-t border-gray-200 pt-4">
        <legend class="text-sm font-semibold text-gray-700">{{ __('Primera variante') }}</legend>
        <p class="text-sm text-gray-500">{{ __('Todo producto tiene al menos una variante. Usa "Única" si no aplica talla o color.') }}</p>
        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="talla" :value="__('Talla')" />
                <x-text-input id="talla" name="talla" type="text" class="mt-1 block w-full"
                    :value="old('talla', 'Única')" required />
                <x-input-error :messages="$errors->get('talla')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="color" :value="__('Color')" />
                <x-text-input id="color" name="color" type="text" class="mt-1 block w-full"
                    :value="old('color', 'Única')" required />
                <x-input-error :messages="$errors->get('color')" class="mt-2" />
            </div>
        </div>
    </fieldset>
@endunless

<div class="mt-6 flex items-center gap-4">
    <x-primary-button>{{ $esEdicion ? __('Guardar cambios') : __('Crear producto') }}</x-primary-button>
    <a href="{{ $esEdicion ? route('admin.productos.show', $producto) : route('admin.productos.index') }}"
       class="text-sm text-gray-600 hover:text-gray-900 underline">
        {{ __('Cancelar') }}
    </a>
</div>
