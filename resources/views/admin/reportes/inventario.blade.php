<x-app-layout>
    <x-page :title="__('Inventario disponible')">
        @include('admin.reportes._nav')

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid grid-cols-2 gap-4">
                <x-reportes.tarjeta :titulo="__('Unidades en stock')" :valor="number_format($unidadesTotal)" />
                <x-reportes.tarjeta :titulo="__('Valor del inventario')" :valor="number_format((float) $valorTotal, 2)"
                                    :detalle="__('stock × costo promedio')" />
            </div>
            <form method="GET" action="{{ route('admin.reportes.inventario') }}">
                <label class="inline-flex items-center gap-2 text-sm text-ink-soft">
                    <input type="checkbox" name="incluir_agotadas" value="1" @checked($incluirAgotadas) onchange="this.form.submit()"
                           class="rounded border-line text-primary-600 focus:ring-2 focus:ring-primary-200">
                    {{ __('Incluir variantes agotadas') }}
                </label>
            </form>
        </div>

        <x-card :title="__('Por categoría')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Categoría') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Unidades') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Valor') }}</th>
                </x-slot>
                @forelse ($porCategoria as $categoria => $totales)
                    <tr>
                        <td class="px-5 py-3 text-ink">{{ $categoria }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format($totales['unidades']) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink">{{ number_format((float) $totales['valor'], 2) }}</td>
                    </tr>
                @empty
                    <x-table-empty :colspan="3" icon="inventario" :title="__('Sin stock disponible')">
                        {{ __('Registra entradas de mercancía para ver el inventario valorizado.') }}
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>

        <x-card :title="__('Detalle por variante')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Producto') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Variante') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Stock') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Costo promedio') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Valor') }}</th>
                </x-slot>
                @forelse ($variantes as $variante)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3">
                            <span class="text-ink">{{ $variante->producto->nombre }}</span>
                            <span class="ml-1 font-mono text-xs text-ink-faint">{{ $variante->producto->codigo_interno }}</span>
                        </td>
                        <td class="px-5 py-3 text-ink-soft">
                            {{ $variante->etiqueta() }}
                            @if ($variante->estaEnStockBajo())
                                <x-badge variant="warning" class="ml-1">{{ __('Stock bajo') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ number_format($variante->stock) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $variante->costo_promedio, 4) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink">{{ number_format((float) $variante->valor_inventario, 2) }}</td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" icon="inventario" :title="__('Sin stock disponible')">
                        @if (! $incluirAgotadas)
                            {{ __('Marca «Incluir variantes agotadas» para ver también las que están en cero.') }}
                        @endif
                    </x-table-empty>
                @endforelse

                @if ($variantes->isNotEmpty())
                    <x-slot name="foot">
                        <tr class="font-semibold">
                            <td class="px-5 py-3" colspan="2">{{ __('Total') }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format($unidadesTotal) }}</td>
                            <td class="px-5 py-3"></td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format((float) $valorTotal, 2) }}</td>
                        </tr>
                    </x-slot>
                @endif
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
