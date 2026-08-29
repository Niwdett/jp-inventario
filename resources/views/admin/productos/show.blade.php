<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $producto->nombre }}
                <span class="ms-2 font-mono text-sm text-gray-500">{{ $producto->codigo_interno }}</span>
            </h2>
            <a href="{{ route('admin.productos.edit', $producto) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Editar producto') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6 flex gap-6">
                @if ($producto->foto)
                    <img src="{{ Storage::url($producto->foto) }}" alt="" class="h-32 w-32 object-cover rounded-md border border-gray-200">
                @endif
                <dl class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                    <dt class="text-gray-500">{{ __('Categoría') }}</dt><dd>{{ $producto->categoria->nombre }}</dd>
                    <dt class="text-gray-500">{{ __('Marca') }}</dt><dd>{{ $producto->marca ?: '—' }}</dd>
                    <dt class="text-gray-500">{{ __('Precio de referencia') }}</dt><dd>{{ number_format((float) $producto->precio_referencia, 2) }}</dd>
                    <dt class="text-gray-500">{{ __('Umbral de stock bajo') }}</dt><dd>{{ $producto->umbral_stock_bajo }}</dd>
                    <dt class="text-gray-500">{{ __('Proveedor') }}</dt><dd>{{ $producto->proveedor ?: '—' }}</dd>
                </dl>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">{{ __('Variantes') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Talla') }}</th>
                            <th class="px-6 py-3">{{ __('Color') }}</th>
                            <th class="px-6 py-3">{{ __('Código') }}</th>
                            <th class="px-6 py-3">{{ __('Stock') }}</th>
                            <th class="px-6 py-3">{{ __('Costo promedio') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($producto->variantes as $variante)
                            <tr>
                                <td class="px-6 py-4">{{ $variante->talla }}</td>
                                <td class="px-6 py-4">{{ $variante->color }}</td>
                                <td class="px-6 py-4 font-mono">{{ $variante->codigo ?: '—' }}</td>
                                <td class="px-6 py-4">
                                    {{ $variante->stock }}
                                    @if ($variante->estaEnStockBajo())
                                        <span class="ms-1 inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">{{ __('Stock bajo') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ number_format((float) $variante->costo_promedio, 4) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.inventario.entradas.create', ['variante_id' => $variante->id]) }}"
                                           class="text-indigo-600 hover:text-indigo-900">{{ __('Entrada') }}</a>
                                        <a href="{{ route('admin.inventario.ajustes.create', ['variante_id' => $variante->id]) }}"
                                           class="text-indigo-600 hover:text-indigo-900">{{ __('Ajuste') }}</a>
                                        <a href="{{ route('admin.productos.variantes.edit', [$producto, $variante]) }}"
                                           class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>
                                        @if ($producto->variantes->count() > 1)
                                            <form method="POST" action="{{ route('admin.productos.variantes.destroy', [$producto, $variante]) }}"
                                                  onsubmit="return confirm('¿Eliminar la variante {{ $variante->etiqueta() }}?')">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <form method="POST" action="{{ route('admin.productos.variantes.store', $producto) }}"
                          class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="talla" :value="__('Talla')" />
                            <x-text-input id="talla" name="talla" type="text" class="mt-1 block w-32" :value="old('talla')" required />
                        </div>
                        <div>
                            <x-input-label for="color" :value="__('Color')" />
                            <x-text-input id="color" name="color" type="text" class="mt-1 block w-32" :value="old('color')" required />
                        </div>
                        <div>
                            <x-input-label for="codigo" :value="__('Código (opcional)')" />
                            <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-40" :value="old('codigo')" />
                        </div>
                        <x-primary-button>{{ __('Agregar variante') }}</x-primary-button>
                    </form>
                    <x-input-error :messages="$errors->get('talla')" class="mt-2" />
                    <x-input-error :messages="$errors->get('color')" class="mt-2" />
                </div>
            </div>

            <a href="{{ route('admin.productos.index') }}" class="inline-block text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('← Volver a productos') }}
            </a>
        </div>
    </div>
</x-app-layout>
