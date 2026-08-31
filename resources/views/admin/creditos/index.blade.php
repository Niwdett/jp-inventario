<x-app-layout>
    <x-page :title="__('Créditos por cobrar')">

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Venta') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Cliente') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Deuda inicial') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Pendiente') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Mora') }}</th>
                </x-slot>

                @forelse ($ventas as $venta)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('ventas.show', $venta) }}" class="font-mono text-sm font-medium text-primary-700 hover:text-primary-800">{{ $venta->numero }}</a>
                        </td>
                        <td class="px-5 py-3">
                            @if ($venta->cliente)
                                <a href="{{ route('admin.clientes.show', $venta->cliente) }}" class="text-primary-700 hover:text-primary-800">{{ $venta->cliente->nombre }}</a>
                            @else
                                <span class="text-ink-faint">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $venta->fecha_venta->format('Y-m-d') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $venta->credito_monto, 2) }}</td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink">{{ number_format((float) $venta->credito_saldo_pendiente, 2) }}</td>
                        <td class="px-5 py-3">
                            @if ($venta->fecha_venta->diffInDays(now()) > \App\Models\Cliente::DIAS_MORA)
                                <x-badge variant="danger">{{ __('En mora') }}</x-badge>
                            @else
                                <x-badge>{{ __('Al día') }}</x-badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" icon="check" tone="positive" :title="__('No hay créditos pendientes de cobro')">
                        {{ __('Todas las ventas a crédito están saldadas.') }}
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>

        {{ $ventas->links() }}
    </x-page>
</x-app-layout>
