@php
    $nombre = explode(' ', trim(auth()->user()->name))[0];
@endphp

<x-app-layout>
    <x-page title="Dashboard" :subtitle="__('Bienvenido de nuevo, :nombre', ['nombre' => $nombre])">

        @isset ($empleado)
            <div class="grid gap-4 sm:grid-cols-2">
                <x-stat :label="__('Mis ventas de hoy')" :value="number_format($empleado['ventas_hoy'])" icon="ventas" />
                <x-stat :label="__('Total vendido hoy')" :value="'$ '.number_format((float) $empleado['total_hoy'], 2)" icon="saldo-favor" />
            </div>

            <x-card>
                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-ink">{{ __('¿Vas a registrar una venta?') }}</p>
                        <p class="mt-0.5 text-sm text-ink-soft">{{ __('Busca el producto, confirma el precio y queda registrada al instante.') }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-4">
                        <x-button :href="route('ventas.create')">
                            <x-icon name="mas" class="size-4" />
                            {{ __('Registrar venta') }}
                        </x-button>
                        <a href="{{ route('ventas.index') }}" class="text-sm font-medium text-primary-700 transition-colors hover:text-primary-800">
                            {{ __('Ver mis ventas') }}
                        </a>
                    </div>
                </div>
            </x-card>
        @endisset

        @isset ($admin)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('admin.reportes.ventas', ['preset' => 'hoy']) }}" class="block transition hover:-translate-y-0.5">
                    <x-stat :label="__('Ventas de hoy')" :value="number_format($admin['ventas_hoy'])"
                            :hint="'$ '.number_format((float) $admin['total_hoy'], 2)" icon="ventas" />
                </a>
                <a href="{{ route('admin.reportes.ganancias', ['preset' => 'mes']) }}" class="block transition hover:-translate-y-0.5">
                    <x-stat :label="__('Ganancia bruta (mes)')" :value="'$ '.number_format((float) $admin['ganancia_mes'], 2)"
                            icon="reportes" :tone="(float) $admin['ganancia_mes'] < 0 ? 'negative' : 'positive'" />
                </a>
                <a href="{{ route('admin.inventario.alertas.index') }}" class="block transition hover:-translate-y-0.5">
                    <x-stat :label="__('Variantes en stock bajo')" :value="number_format($admin['variantes_stock_bajo'])"
                            icon="inventario" :tone="$admin['variantes_stock_bajo'] > 0 ? 'warning' : 'neutral'" />
                </a>
                <a href="{{ route('admin.creditos.index') }}" class="block transition hover:-translate-y-0.5">
                    <x-stat :label="__('Clientes en mora')" :value="number_format($admin['clientes_en_mora'])"
                            icon="clientes" :tone="$admin['clientes_en_mora'] > 0 ? 'negative' : 'neutral'" />
                </a>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <x-card :title="__('Resumen del mes')">
                    <dl class="-my-1 divide-y divide-line text-sm">
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-ink-soft">{{ __('Ventas del mes') }}</dt>
                            <dd class="text-right font-medium tabular-nums text-ink">
                                {{ number_format($admin['ventas_mes']) }}
                                <span class="text-ink-faint">·</span>
                                $ {{ number_format((float) $admin['total_mes'], 2) }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-ink-soft">{{ __('Crédito por cobrar') }}</dt>
                            <dd class="font-medium tabular-nums text-ink">$ {{ number_format((float) $admin['credito_por_cobrar'], 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-ink-soft">{{ __('Saldo a favor de clientes') }}</dt>
                            <dd class="font-medium tabular-nums text-ink">$ {{ number_format((float) $admin['saldo_favor_clientes'], 2) }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card :title="__('Productos más vendidos del mes')" flush class="lg:col-span-2">
                    <x-table>
                        <x-slot name="head">
                            <th class="px-5 py-3 font-medium">{{ __('Producto') }}</th>
                            <th class="px-5 py-3 text-right font-medium">{{ __('Unidades') }}</th>
                            <th class="px-5 py-3 text-right font-medium">{{ __('Ingreso') }}</th>
                        </x-slot>
                        @forelse ($admin['top_productos'] as $fila)
                            <tr class="transition-colors hover:bg-surface-sunken/60">
                                <td class="px-5 py-3">
                                    <span class="font-medium text-ink">{{ $fila->nombre }}</span>
                                    <span class="ml-1.5 font-mono text-xs text-ink-faint">{{ $fila->codigo_interno }}</span>
                                </td>
                                <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format($fila->unidades) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-ink-soft">$ {{ number_format((float) $fila->ingreso, 2) }}</td>
                            </tr>
                        @empty
                            <x-table-empty :colspan="3" icon="ventas" :title="__('Aún no hay ventas este mes')">
                                {{ __('Cuando registres ventas verás aquí los productos que más se mueven.') }}
                            </x-table-empty>
                        @endforelse
                    </x-table>
                </x-card>
            </div>
        @endisset

    </x-page>
</x-app-layout>
