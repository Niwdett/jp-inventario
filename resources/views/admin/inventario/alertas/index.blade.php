<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Alertas de stock bajo') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('admin.inventario._nav')

            @if ($variantes->isEmpty())
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
                    {{ __('Ninguna variante está en stock bajo. Todo el inventario está por encima de su umbral.') }}
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-md p-4">
                    {{ trans_choice('{1} :count variante necesita reposición.|[2,*] :count variantes necesitan reposición.', $variantes->count(), ['count' => $variantes->count()]) }}
                </div>

                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('Producto') }}</th>
                                <th class="px-6 py-3">{{ __('Variante') }}</th>
                                <th class="px-6 py-3">{{ __('Stock') }}</th>
                                <th class="px-6 py-3">{{ __('Umbral') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($variantes as $variante)
                                <tr>
                                    <td class="px-6 py-4">
                                        {{ $variante->producto->nombre }}
                                        <span class="font-mono text-xs text-gray-400">{{ $variante->producto->codigo_interno }}</span>
                                    </td>
                                    <td class="px-6 py-4">{{ $variante->etiqueta() }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium">
                                            {{ $variante->stock }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ $variante->producto->umbral_stock_bajo }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.inventario.entradas.create', ['variante_id' => $variante->id]) }}"
                                           class="text-indigo-600 hover:text-indigo-900">{{ __('Registrar entrada') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
