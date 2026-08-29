<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ajustar inventario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="mb-4 text-sm text-gray-500">
                    {{ __('Usa esta pantalla cuando el conteo físico no coincide con el sistema (RN-10). El stock queda igual a la cantidad contada.') }}
                </p>

                <form method="POST" action="{{ route('admin.inventario.ajustes.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="variante_id" :value="__('Variante')" />
                        <x-select-input id="variante_id" name="variante_id" class="mt-1 block w-full"
                            :options="$variantes" :selected="$varianteSeleccionada"
                            :placeholder="__('— Selecciona una variante —')" required />
                        <x-input-error :messages="$errors->get('variante_id')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="cantidad_nueva" :value="__('Cantidad contada')" />
                        <x-text-input id="cantidad_nueva" name="cantidad_nueva" type="number" min="0" step="1"
                            class="mt-1 block w-full" :value="old('cantidad_nueva')" required />
                        <x-input-error :messages="$errors->get('cantidad_nueva')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="motivo" :value="__('Motivo (opcional)')" />
                        <x-text-input id="motivo" name="motivo" type="text"
                            class="mt-1 block w-full" :value="old('motivo')" />
                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Ajustar') }}</x-primary-button>
                        <a href="{{ route('admin.inventario.ajustes.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Cancelar') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
