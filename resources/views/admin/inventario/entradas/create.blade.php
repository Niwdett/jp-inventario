<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registrar entrada de mercancía') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="mb-4 text-sm text-gray-500">
                    {{ __('Al registrar la entrada se suma al stock y se recalcula el costo promedio de la variante.') }}
                </p>

                <form method="POST" action="{{ route('admin.inventario.entradas.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="variante_id" :value="__('Variante')" />
                        <x-select-input id="variante_id" name="variante_id" class="mt-1 block w-full"
                            :options="$variantes" :selected="$varianteSeleccionada"
                            :placeholder="__('— Selecciona una variante —')" required />
                        <x-input-error :messages="$errors->get('variante_id')" class="mt-2" />
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="cantidad" :value="__('Cantidad')" />
                            <x-text-input id="cantidad" name="cantidad" type="number" min="1" step="1"
                                class="mt-1 block w-full" :value="old('cantidad')" required />
                            <x-input-error :messages="$errors->get('cantidad')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="costo_unitario" :value="__('Costo unitario')" />
                            <x-text-input id="costo_unitario" name="costo_unitario" type="number" min="0" step="0.0001"
                                class="mt-1 block w-full" :value="old('costo_unitario')" required />
                            <x-input-error :messages="$errors->get('costo_unitario')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="fecha" :value="__('Fecha')" />
                            <x-text-input id="fecha" name="fecha" type="date"
                                class="mt-1 block w-full" :value="old('fecha', now()->toDateString())"
                                max="{{ now()->toDateString() }}" required />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="proveedor" :value="__('Proveedor (opcional)')" />
                            <x-text-input id="proveedor" name="proveedor" type="text"
                                class="mt-1 block w-full" :value="old('proveedor')" />
                            <x-input-error :messages="$errors->get('proveedor')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Registrar entrada') }}</x-primary-button>
                        <a href="{{ route('admin.inventario.entradas.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Cancelar') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
