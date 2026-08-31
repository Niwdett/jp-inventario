<x-app-layout>
    <x-page :title="$cliente->nombre" :subtitle="__('Cliente')">
        <x-slot name="actions">
            @can('update', $cliente)
                <x-button variant="secondary" :href="route('admin.clientes.edit', $cliente)">
                    <x-icon name="editar" class="size-4" />
                    {{ __('Editar') }}
                </x-button>
            @endcan
            <x-button variant="secondary" :href="route('admin.clientes.index')">
                <x-icon name="arrow-left" class="size-4" />
                {{ __('Volver') }}
            </x-button>
        </x-slot>

        <x-card>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-3">
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Teléfono') }}</dt><dd class="mt-0.5 text-ink">{{ $cliente->telefono ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Cédula') }}</dt><dd class="mt-0.5 font-mono text-ink">{{ $cliente->cedula ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Saldo a favor') }}</dt>
                    <dd class="mt-0.5 font-semibold tabular-nums {{ (float) $cliente->saldo_favor > 0 ? 'text-success-700' : 'text-ink' }}">
                        <x-money :value="$cliente->saldo_favor" />
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card :title="__('Ventas a crédito con saldo pendiente')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Venta') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Deuda inicial') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Pendiente') }}</th>
                </x-slot>
                @forelse ($cliente->ventasACredito as $venta)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('ventas.show', $venta) }}" class="font-mono text-sm font-medium text-primary-700 hover:text-primary-800">{{ $venta->numero }}</a>
                        </td>
                        <td class="px-5 py-3 text-ink-soft">{{ $venta->fecha_venta->format('Y-m-d') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft"><x-money :value="$venta->credito_monto" /></td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink"><x-money :value="$venta->credito_saldo_pendiente" /></td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4" icon="check" tone="positive" :title="__('Sin deudas pendientes')" />
                @endforelse
            </x-table>
        </x-card>

        <x-card :title="__('Movimientos de saldo a favor')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Tipo') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Monto') }}</th>
                </x-slot>
                @forelse ($cliente->saldoFavorMovimientos as $movimiento)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $movimiento->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3 text-ink">{{ $movimiento->tipo->label() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ (float) $movimiento->monto < 0 ? 'text-danger-600' : 'text-success-700' }}">
                            <x-money :value="$movimiento->monto" />
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="3" icon="saldo-favor" :title="__('Sin movimientos de saldo a favor')" />
                @endforelse
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
