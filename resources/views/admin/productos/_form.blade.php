@props(['producto' => null, 'categorias' => null])

@php($esEdicion = ! is_null($producto))

<div class="space-y-5">
    <div>
        <x-input-label for="categoria_id" :value="__('Categoría')" />
        @if ($esEdicion)
            {{-- La categoría no cambia tras el alta: el código interno quedaría inconsistente con el prefijo. --}}
            <p class="mt-1.5 text-sm text-ink">
                {{ $producto->categoria->nombre }}
                <span class="text-ink-faint">({{ __('no editable') }})</span>
            </p>
        @else
            <x-select-input id="categoria_id" name="categoria_id" class="mt-1.5"
                :options="$categorias->pluck('nombre', 'id')"
                :selected="old('categoria_id')"
                :placeholder="__('— Selecciona una categoría —')" required />
            <x-input-error :messages="$errors->get('categoria_id')" class="mt-1.5" />
        @endif
    </div>

    <div>
        <x-input-label for="nombre" :value="__('Nombre')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5"
            :value="old('nombre', $producto?->nombre)" required autofocus />
        <x-input-error :messages="$errors->get('nombre')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="marca" :value="__('Marca (opcional)')" />
        <x-text-input id="marca" name="marca" type="text" class="mt-1.5"
            :value="old('marca', $producto?->marca)" />
        <x-input-error :messages="$errors->get('marca')" class="mt-1.5" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="precio_referencia" :value="__('Precio de referencia')" />
            <x-text-input id="precio_referencia" name="precio_referencia" type="number" step="0.01" min="0"
                class="mt-1.5" :value="old('precio_referencia', $producto?->precio_referencia)" required />
            <x-input-error :messages="$errors->get('precio_referencia')" class="mt-1.5" />
        </div>
        <div>
            <x-input-label for="umbral_stock_bajo" :value="__('Umbral de stock bajo')" />
            <x-text-input id="umbral_stock_bajo" name="umbral_stock_bajo" type="number" min="0"
                class="mt-1.5" :value="old('umbral_stock_bajo', $producto?->umbral_stock_bajo ?? 0)" required />
            <p class="mt-1.5 text-xs text-ink-faint">{{ __('Se avisa cuando una variante llega a esta cantidad o menos.') }}</p>
            <x-input-error :messages="$errors->get('umbral_stock_bajo')" class="mt-1.5" />
        </div>
    </div>

    <div>
        <x-input-label for="proveedor" :value="__('Proveedor (opcional)')" />
        <x-text-input id="proveedor" name="proveedor" type="text" class="mt-1.5"
            :value="old('proveedor', $producto?->proveedor)" />
        <x-input-error :messages="$errors->get('proveedor')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="foto" :value="__('Foto (opcional)')" />
        @if ($esEdicion && $producto->foto)
            <img src="{{ Storage::url($producto->foto) }}" alt="" class="mt-1.5 h-24 w-24 rounded-lg border border-line object-cover">
        @endif
        <input id="foto" name="foto" type="file" accept="image/*"
            class="mt-1.5 block w-full text-sm text-ink-soft file:mr-4 file:rounded-lg file:border-0 file:bg-surface-sunken file:px-4 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-line">
        <x-input-error :messages="$errors->get('foto')" class="mt-1.5" />
    </div>

    @unless ($esEdicion)
        <fieldset class="rounded-lg border border-line p-4">
            <legend class="px-1 text-sm font-semibold text-ink">{{ __('Primera variante') }}</legend>
            <p class="text-sm text-ink-soft">{{ __('Todo producto tiene al menos una variante. Usa "Única" si no aplica talla o color.') }}</p>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="talla" :value="__('Talla')" />
                    <x-text-input id="talla" name="talla" type="text" class="mt-1.5"
                        :value="old('talla', 'Única')" required />
                    <x-input-error :messages="$errors->get('talla')" class="mt-1.5" />
                </div>
                <div>
                    <x-input-label for="color" :value="__('Color')" />
                    <x-text-input id="color" name="color" type="text" class="mt-1.5"
                        :value="old('color', 'Única')" required />
                    <x-input-error :messages="$errors->get('color')" class="mt-1.5" />
                </div>
            </div>
        </fieldset>
    @endunless
</div>

<div class="mt-6 flex items-center gap-3">
    <x-button>{{ $esEdicion ? __('Guardar cambios') : __('Crear producto') }}</x-button>
    <x-button variant="ghost" :href="$esEdicion ? route('admin.productos.show', $producto) : route('admin.productos.index')">
        {{ __('Cancelar') }}
    </x-button>
</div>
