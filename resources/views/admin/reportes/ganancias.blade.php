@php
    $tono = fn ($valor) => (float) $valor < 0 ? 'negativo' : 'positivo';
@endphp

<x-app-layout>
    <x-page :title="__('Reporte de ganancias')">
        @include('admin.reportes._nav')
        @include('admin.reportes._filtros', ['ruta' => 'admin.reportes.ganancias', 'comparable' => true])

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
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
            <x-card :title="__('Comparación de periodos')" flush>
                <x-table>
                    <x-slot name="head">
                        <th class="px-5 py-3 font-medium">{{ __('Indicador') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Periodo anterior') }}<br><span class="font-normal normal-case">{{ $comparacion['periodo']['desde']->format('Y-m-d') }} — {{ $comparacion['periodo']['hasta']->format('Y-m-d') }}</span></th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Periodo actual') }}<br><span class="font-normal normal-case">{{ $periodo['etiqueta'] }}</span></th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Variación') }}</th>
                    </x-slot>
                    @foreach (['ingreso' => __('Ingreso'), 'ganancia_neta' => __('Ganancia neta')] as $clave => $texto)
                        @php
                            $previo = (float) $comparacion['resumen'][$clave];
                            $actual = (float) $resumen[$clave];
                            $delta = $actual - $previo;
                        @endphp
                        <tr>
                            <td class="px-5 py-3 text-ink">{{ $texto }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format($previo, 2) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-ink">{{ number_format($actual, 2) }}</td>
                            <td class="px-5 py-3 text-right tabular-nums {{ $delta < 0 ? 'text-danger-600' : 'text-success-700' }}">
                                {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                                @if ($previo != 0.0)
                                    <span class="text-xs text-ink-faint">({{ number_format($delta / abs($previo) * 100, 1) }}%)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        <x-card :title="__('Ganancia por producto')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Producto') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Unidades') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Ingreso') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Ganancia bruta') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Devueltas') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Ganancia neta') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Margen') }}</th>
                </x-slot>
                @forelse ($porProducto as $fila)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3">
                            <span class="text-ink">{{ $fila->nombre }}</span>
                            <span class="ml-1 font-mono text-xs text-ink-faint">{{ $fila->codigo }}</span>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format($fila->unidades) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $fila->ingreso, 2) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $fila->ganancia_bruta, 2) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $fila->unidades_devueltas ? number_format($fila->unidades_devueltas) : '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums {{ (float) $fila->ganancia_neta < 0 ? 'text-danger-600' : 'text-ink' }}">
                            {{ number_format((float) $fila->ganancia_neta, 2) }}
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $fila->margen }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-ink-faint">{{ __('Sin ventas en el periodo.') }}</td></tr>
                @endforelse
            </x-table>
        </x-card>

        <x-card flush>
            <x-slot name="title">
                {{ __('Ganancia por venta') }}
                <span class="text-xs font-normal text-ink-faint">— {{ __('bruta, sin descontar devoluciones') }}</span>
            </x-slot>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Venta') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Cliente') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Ingreso') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Costo') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Ganancia') }}</th>
                </x-slot>
                @forelse ($porVenta as $fila)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('ventas.show', $fila->id) }}" class="font-mono text-sm font-medium text-primary-700 hover:text-primary-800">{{ $fila->numero }}</a>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ \Illuminate\Support\Carbon::parse($fila->fecha_venta)->format('Y-m-d') }}</td>
                        <td class="px-5 py-3 text-ink">{{ $fila->cliente ?? '—' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $fila->ingreso, 2) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $fila->costo, 2) }}</td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums {{ (float) $fila->ganancia < 0 ? 'text-danger-600' : 'text-ink' }}">
                            {{ number_format((float) $fila->ganancia, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-ink-faint">{{ __('Sin ventas en el periodo.') }}</td></tr>
                @endforelse
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
