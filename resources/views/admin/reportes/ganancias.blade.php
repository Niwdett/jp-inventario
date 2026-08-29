@php
    $tono = fn ($valor) => (float) $valor < 0 ? 'negativo' : 'positivo';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Reporte de ganancias') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin.reportes._nav')
            @include('admin.reportes._filtros', ['ruta' => 'admin.reportes.ganancias', 'comparable' => true])

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-reportes.tarjeta :titulo="__('Ingreso por ventas')" :valor="number_format((float) $resumen['ingreso'], 2)"
                                    :detalle="$resumen['ventas'] . ' ' . __('ventas confirmadas')" />
                <x-reportes.tarjeta :titulo="__('Ganancia bruta')" :valor="number_format((float) $resumen['ganancia_bruta'], 2)"
                                    :tono="$tono($resumen['ganancia_bruta'])" />
                <x-reportes.tarjeta :titulo="__('Ajuste por devoluciones')"
                                    :valor="((float) $resumen['ganancia_revertida'] != 0.0 ? '-' : '') . number_format((float) $resumen['ganancia_revertida'], 2)"
                                    :detalle="__('devoluciones validadas del periodo')" :tono="(float) $resumen['ganancia_revertida'] > 0 ? 'alerta' : 'neutral'" />
                <x-reportes.tarjeta :titulo="__('Ganancia neta')" :valor="number_format((float) $resumen['ganancia_neta'], 2)"
                                    :detalle="__('margen') . ' ' . $resumen['margen'] . '%'" :tono="$tono($resumen['ganancia_neta'])" />
            </div>

            @if ($comparacion)
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Comparación de periodos') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('Indicador') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Periodo anterior') }}<br><span class="normal-case font-normal">{{ $comparacion['periodo']['desde']->format('Y-m-d') }} — {{ $comparacion['periodo']['hasta']->format('Y-m-d') }}</span></th>
                                <th class="px-6 py-3 text-right">{{ __('Periodo actual') }}<br><span class="normal-case font-normal">{{ $periodo['etiqueta'] }}</span></th>
                                <th class="px-6 py-3 text-right">{{ __('Variación') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach (['ingreso' => __('Ingreso'), 'ganancia_neta' => __('Ganancia neta')] as $clave => $texto)
                                @php
                                    $previo = (float) $comparacion['resumen'][$clave];
                                    $actual = (float) $resumen[$clave];
                                    $delta = $actual - $previo;
                                @endphp
                                <tr>
                                    <td class="px-6 py-3">{{ $texto }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format($previo, 2) }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format($actual, 2) }}</td>
                                    <td class="px-6 py-3 text-right {{ $delta < 0 ? 'text-red-700' : 'text-green-700' }}">
                                        {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                                        @if ($previo != 0.0)
                                            <span class="text-xs text-gray-400">({{ number_format($delta / abs($previo) * 100, 1) }}%)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Ganancia por producto') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('Producto') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Unidades') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Ingreso') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Ganancia bruta') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Devueltas') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Ganancia neta') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Margen') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($porProducto as $fila)
                                <tr>
                                    <td class="px-6 py-3">
                                        {{ $fila->nombre }}
                                        <span class="font-mono text-xs text-gray-400">{{ $fila->codigo }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right">{{ number_format($fila->unidades) }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format((float) $fila->ingreso, 2) }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format((float) $fila->ganancia_bruta, 2) }}</td>
                                    <td class="px-6 py-3 text-right">{{ $fila->unidades_devueltas ? number_format($fila->unidades_devueltas) : '—' }}</td>
                                    <td class="px-6 py-3 text-right font-semibold {{ (float) $fila->ganancia_neta < 0 ? 'text-red-700' : 'text-gray-900' }}">
                                        {{ number_format((float) $fila->ganancia_neta, 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-right">{{ $fila->margen }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">{{ __('Sin ventas en el periodo.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">
                    {{ __('Ganancia por venta') }}
                    <span class="font-normal text-xs text-gray-400">— {{ __('bruta, sin descontar devoluciones') }}</span>
                </h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Venta') }}</th>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Cliente') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Ingreso') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Costo') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Ganancia') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($porVenta as $fila)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('ventas.show', $fila->id) }}" class="text-indigo-600 hover:text-indigo-900 font-mono">{{ $fila->numero }}</a>
                                </td>
                                <td class="px-6 py-3">{{ \Illuminate\Support\Carbon::parse($fila->fecha_venta)->format('Y-m-d') }}</td>
                                <td class="px-6 py-3">{{ $fila->cliente ?? '—' }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $fila->ingreso, 2) }}</td>
                                <td class="px-6 py-3 text-right">{{ number_format((float) $fila->costo, 2) }}</td>
                                <td class="px-6 py-3 text-right font-semibold {{ (float) $fila->ganancia < 0 ? 'text-red-700' : 'text-gray-900' }}">
                                    {{ number_format((float) $fila->ganancia, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">{{ __('Sin ventas en el periodo.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
