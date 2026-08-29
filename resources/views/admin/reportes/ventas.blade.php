<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Reporte de ventas por periodo') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.reportes._nav')
            @include('admin.reportes._filtros', ['ruta' => 'admin.reportes.ventas'])

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-reportes.tarjeta :titulo="__('Ventas')" :valor="number_format($resumen->ventas)" />
                <x-reportes.tarjeta :titulo="__('Subtotal')" :valor="number_format((float) $resumen->subtotal, 2)" />
                <x-reportes.tarjeta :titulo="__('Descuentos')" :valor="number_format((float) $resumen->descuento, 2)" />
                <x-reportes.tarjeta :titulo="__('Total cobrado')" :valor="number_format((float) $resumen->total, 2)" />
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Por método de pago') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('Método') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Ventas') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($porMetodo as $fila)
                                <tr>
                                    <td class="px-6 py-3">{{ $fila->metodo_pago->label() }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format($fila->ventas) }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format((float) $fila->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">{{ __('Sin ventas en el periodo.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-2">{{ __('Devoluciones del periodo') }}</h3>
                    <p class="text-sm text-gray-600">
                        {{ trans_choice('{0}Ninguna devolución validada.|{1}:count devolución validada|[2,*]:count devoluciones validadas', $devoluciones->total, ['count' => $devoluciones->total]) }}
                        @if ($devoluciones->total > 0)
                            — {{ __('saldo a favor generado:') }} <span class="font-medium">{{ number_format((float) $devoluciones->saldo_generado, 2) }}</span>
                        @endif
                    </p>
                    <p class="mt-2 text-xs text-gray-400">{{ __('Su efecto en la utilidad se refleja en el reporte de Ganancias.') }}</p>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Detalle por día') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Día') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Ventas') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($porDia as $fila)
                            <tr>
                                <td class="px-6 py-3">{{ $fila->dia }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format($fila->ventas) }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $fila->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">{{ __('Sin ventas en el periodo.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
