<x-app-layout>
    <x-page :title="__('Registrar entrada de mercancía')">
        <x-card class="max-w-xl">
            <p class="mb-5 text-sm text-ink-soft">
                {{ __('Al registrar la entrada se suma al stock y se recalcula el costo promedio de la variante.') }}
            </p>

            <form method="POST" action="{{ route('admin.inventario.entradas.store') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="variante_id" :value="__('Variante')" />
                    <x-select-input id="variante_id" name="variante_id" class="mt-1.5"
                        :options="$variantes" :selected="$varianteSeleccionada"
                        :placeholder="__('— Selecciona una variante —')" required />
                    <x-input-error :messages="$errors->get('variante_id')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="cantidad" :value="__('Cantidad')" />
                        <x-text-input id="cantidad" name="cantidad" type="number" min="1" step="1"
                            class="mt-1.5" :value="old('cantidad')" required />
                        <x-input-error :messages="$errors->get('cantidad')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="costo_unitario" :value="__('Costo unitario')" />
                        <x-text-input id="costo_unitario" name="costo_unitario" type="number" min="0" step="0.0001"
                            class="mt-1.5" :value="old('costo_unitario')" required />
                        <x-input-error :messages="$errors->get('costo_unitario')" class="mt-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="fecha" :value="__('Fecha')" />
                        <x-text-input id="fecha" name="fecha" type="date"
                            class="mt-1.5" :value="old('fecha', now()->toDateString())"
                            max="{{ now()->toDateString() }}" required />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="proveedor" :value="__('Proveedor (opcional)')" />
                        <x-text-input id="proveedor" name="proveedor" type="text"
                            class="mt-1.5" :value="old('proveedor')" />
                        <x-input-error :messages="$errors->get('proveedor')" class="mt-1.5" />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-button>{{ __('Registrar entrada') }}</x-button>
                    <x-button variant="ghost" :href="route('admin.inventario.entradas.index')">{{ __('Cancelar') }}</x-button>
                </div>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
