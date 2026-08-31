<x-app-layout>
    <x-page :title="__('Editar variante')" :subtitle="$producto->nombre">
        <x-card class="max-w-xl">
            <p class="mb-5 text-sm text-ink-soft">
                {{ __('El stock y el costo promedio no se editan aquí: cambian por entradas, ventas y ajustes de inventario.') }}
            </p>

            <form method="POST" action="{{ route('admin.productos.variantes.update', [$producto, $variante]) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="talla" :value="__('Talla')" />
                        <x-text-input id="talla" name="talla" type="text" class="mt-1.5"
                            :value="old('talla', $variante->talla)" required autofocus />
                        <x-input-error :messages="$errors->get('talla')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="color" :value="__('Color')" />
                        <x-text-input id="color" name="color" type="text" class="mt-1.5"
                            :value="old('color', $variante->color)" required />
                        <x-input-error :messages="$errors->get('color')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="codigo" :value="__('Código (opcional)')" />
                    <x-text-input id="codigo" name="codigo" type="text" class="mt-1.5"
                        :value="old('codigo', $variante->codigo)" />
                    <x-input-error :messages="$errors->get('codigo')" class="mt-1.5" />
                </div>

                <div class="flex items-center gap-3">
                    <x-button>{{ __('Guardar cambios') }}</x-button>
                    <x-button variant="ghost" :href="route('admin.productos.show', $producto)">{{ __('Cancelar') }}</x-button>
                </div>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
