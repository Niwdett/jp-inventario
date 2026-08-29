<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar variante') }} — {{ $producto->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="mb-4 text-sm text-gray-500">
                    {{ __('El stock y el costo promedio no se editan aquí: cambian por entradas, ventas y ajustes de inventario.') }}
                </p>
                <form method="POST" action="{{ route('admin.productos.variantes.update', [$producto, $variante]) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="talla" :value="__('Talla')" />
                            <x-text-input id="talla" name="talla" type="text" class="mt-1 block w-full"
                                :value="old('talla', $variante->talla)" required autofocus />
                            <x-input-error :messages="$errors->get('talla')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="color" :value="__('Color')" />
                            <x-text-input id="color" name="color" type="text" class="mt-1 block w-full"
                                :value="old('color', $variante->color)" required />
                            <x-input-error :messages="$errors->get('color')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="codigo" :value="__('Código (opcional)')" />
                        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full"
                            :value="old('codigo', $variante->codigo)" />
                        <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar cambios') }}</x-primary-button>
                        <a href="{{ route('admin.productos.show', $producto) }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Cancelar') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
