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

        <x-card :title="__('Detalle por día')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Día') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Ventas') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Total') }}</th>
                </x-slot>
                @forelse ($porDia as $fila)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3 text-ink">{{ $fila->dia }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format($fila->ventas) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink"><x-money :value="$fila->total" /></td>
                    </tr>
                @empty
                    <x-table-empty :colspan="3" icon="calendario" :title="__('Sin ventas en el periodo seleccionado')">
                        {{ __('Prueba con otro rango de fechas en el filtro de arriba.') }}
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
