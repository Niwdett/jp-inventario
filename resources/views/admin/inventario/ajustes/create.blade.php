<x-app-layout>
    <x-page :title="__('Ajustar inventario')">
        <x-card class="max-w-xl">
            <p class="mb-5 text-sm text-ink-soft">
                {{ __('Usa esta pantalla cuando el conteo físico no coincide con el sistema (RN-10). El stock queda igual a la cantidad contada.') }}
            </p>

            <form method="POST" action="{{ route('admin.inventario.ajustes.store') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="variante_id" :value="__('Variante')" />
                    <x-select-input id="variante_id" name="variante_id" class="mt-1.5"
                        :options="$variantes" :selected="$varianteSeleccionada"
                        :placeholder="__('— Selecciona una variante —')" required />
                    <x-input-error :messages="$errors->get('variante_id')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="cantidad_nueva" :value="__('Cantidad contada')" />
                    <x-text-input id="cantidad_nueva" name="cantidad_nueva" type="number" min="0" step="1"
                        class="mt-1.5" :value="old('cantidad_nueva')" required />
                    <x-input-error :messages="$errors->get('cantidad_nueva')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="motivo" :value="__('Motivo (opcional)')" />
                    <x-text-input id="motivo" name="motivo" type="text" class="mt-1.5" :value="old('motivo')" />
                    <x-input-error :messages="$errors->get('motivo')" class="mt-1.5" />
                </div>

                <div class="flex items-center gap-3">
                    <x-button>{{ __('Ajustar') }}</x-button>
                    <x-button variant="ghost" :href="route('admin.inventario.ajustes.index')">{{ __('Cancelar') }}</x-button>
                </div>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
