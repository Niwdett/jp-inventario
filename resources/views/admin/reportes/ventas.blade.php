<x-app-layout>
    <x-page :title="__('Reporte de ventas por periodo')">
        <x-slot name="actions">
            <x-print-button />
        </x-slot>
        @include('admin.reportes._nav')
        @include('admin.reportes._filtros', ['ruta' => 'admin.reportes.ventas'])

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <x-reportes.tarjeta :titulo="__('Ventas')" :valor="number_format($resumen->ventas)" />
            <x-reportes.tarjeta :titulo="__('Subtotal')" :valor="money($resumen->subtotal)" />
            <x-reportes.tarjeta :titulo="__('Descuentos')" :valor="money($resumen->descuento)" />
            <x-reportes.tarjeta :titulo="__('Total cobrado')" :valor="money($resumen->total)" />
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <x-card :title="__('Por método de pago')" flush>
                <x-table>
                    <x-slot name="head">
                        <th class="px-5 py-3 font-medium">{{ __('Método') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Ventas') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Total') }}</th>
                    </x-slot>
                    @forelse ($porMetodo as $fila)
                        <tr>
                            <td class="px-5 py-3 text-ink">{{ $fila->metodo_pago->label() }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format($fila->ventas) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-ink"><x-money :value="$fila->total" /></td>
                        </tr>
                    @empty
                        <x-table-empty :colspan="3" icon="calendario" :title="__('Sin ventas en el periodo seleccionado')" />
                    @endforelse
                </x-table>
            </x-card>

            <x-card :title="__('Devoluciones del periodo')">
                <p class="text-sm text-ink-soft">
                    {{ trans_choice('{0}Ninguna devolución validada.|{1}:count devolución validada|[2,*]:count devoluciones validadas', $devoluciones->total, ['count' => $devoluciones->total]) }}
                    @if ($devoluciones->total > 0)
                        — {{ __('saldo a favor generado:') }} <span class="font-medium text-ink"><x-money :value="$devoluciones->saldo_generado" /></span>
                    @endif
                </p>
                <p class="mt-2 text-xs text-ink-faint">{{ __('Su efecto en la utilidad se refleja en el reporte de Ganancias.') }}</p>
            </x-card>
        </div>

        <x-card :title="__('Detalle por día')" :subtitle="__('Toca un día para ver sus ventas')" flush>
            @forelse ($porDia as $fila)
                <div x-data="{ open: false }" class="border-b border-line last:border-b-0">
                    <button type="button" x-on:click="open = ! open"
                            class="flex w-full items-center gap-3 px-5 py-3 text-left text-sm transition-colors hover:bg-surface-sunken/60">
                        <x-icon name="chevron-right" class="size-3.5 shrink-0 text-ink-faint transition-transform print:hidden"
                                x-bind:class="{ 'rotate-90': open }" />
                        <span class="font-medium text-ink">{{ $fila['dia'] }}</span>
                        <span class="ml-auto tabular-nums text-ink-soft">
                            {{ trans_choice('{1}:count venta|[2,*]:count ventas', $fila['ventas'], ['count' => number_format($fila['ventas'])]) }}
                        </span>
                        <span class="w-28 shrink-0 text-right font-medium tabular-nums text-ink"><x-money :value="$fila['total']" /></span>
                    </button>

                    <div x-show="open" style="display: none;"
                         class="border-l-2 border-primary-200 bg-surface-sunken/40 px-5 print:!block">
                        @foreach ($fila['detalle'] as $venta)
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-0.5 border-t border-line py-2 text-sm first:border-t-0">
                                <a href="{{ route('ventas.show', $venta) }}"
                                   class="font-mono font-medium text-primary-700 hover:text-primary-800">{{ $venta->numero }}</a>
                                <span class="min-w-0 flex-1 truncate text-ink-soft">{{ $venta->cliente?->nombre ?? __('Contado') }}</span>
                                <span class="text-ink-faint">{{ $venta->metodo_pago->label() }}</span>
                                <span class="tabular-nums text-ink"><x-money :value="$venta->total" /></span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-empty-state icon="calendario" :title="__('Sin ventas en el periodo seleccionado')">
                    {{ __('Prueba con otro rango de fechas en el filtro de arriba.') }}
                </x-empty-state>
            @endforelse
        </x-card>
    </x-page>
</x-app-layout>
