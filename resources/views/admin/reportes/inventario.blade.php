<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Inventario disponible') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.reportes._nav')

            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-reportes.tarjeta :titulo="__('Unidades en stock')" :valor="number_format($unidadesTotal)" />
                    <x-reportes.tarjeta :titulo="__('Valor del inventario')" :valor="number_format((float) $valorTotal, 2)"
                                        :detalle="__('stock × costo promedio')" />
                </div>
                <form method="GET" action="{{ route('admin.reportes.inventario') }}">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="incluir_agotadas" value="1" @checked($incluirAgotadas) onchange="this.form.submit()"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('Incluir variantes agotadas') }}
                    </label>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Por categoría') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Categoría') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Unidades') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Valor') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($porCategoria as $categoria => $totales)
                            <tr>
                                <td class="px-6 py-3">{{ $categoria }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format($totales['unidades']) }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $totales['valor'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">{{ __('Sin stock disponible.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Detalle por variante') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Producto') }}</th>
                            <th class="px-6 py-3">{{ __('Variante') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Stock') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Costo promedio') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Valor') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($variantes as $variante)
                            <tr>
                                <td class="px-6 py-3">
                                    {{ $variante->producto->nombre }}
                                    <span class="font-mono text-xs text-gray-400">{{ $variante->producto->codigo_interno }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    {{ $variante->etiqueta() }}
                                    @if ($variante->estaEnStockBajo())
                                        <span class="ms-1 inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">{{ __('Stock bajo') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">{{ number_format($variante->stock) }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $variante->costo_promedio, 4) }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $variante->valor_inventario, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">{{ __('Sin stock disponible.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($variantes->isNotEmpty())
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-6 py-3" colspan="2">{{ __('Total') }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format($unidadesTotal) }}</td>
                                <td class="px-6 py-3"></td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $valorTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
