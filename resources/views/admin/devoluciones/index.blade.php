<x-app-layout>
    <x-page :title="__('Devoluciones')">

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Venta') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Cliente') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Estado') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Saldo generado') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Validó') }}</th>
                </x-slot>

                @forelse ($devoluciones as $devolucion)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $devolucion->fecha->format('Y-m-d') }}</td>
                        <td class="px-5 py-3">
                            <a href="{{ route('ventas.show', $devolucion->venta) }}" class="font-mono text-sm font-medium text-primary-700 hover:text-primary-800">{{ $devolucion->venta->numero }}</a>
                        </td>
                        <td class="px-5 py-3 text-ink">{{ $devolucion->venta->cliente?->nombre ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($devolucion->estado === \App\Enums\EstadoDevolucion::Validada)
                                <x-badge variant="success">{{ __('Validada') }}</x-badge>
                            @else
                                <x-badge>{{ __('Rechazada') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink">{{ number_format((float) $devolucion->saldo_generado, 2) }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $devolucion->usuario?->name }}</td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" icon="devoluciones" :title="__('Aún no se han registrado devoluciones')">
                        {{ __('Las devoluciones se inician desde el detalle de una venta entregada.') }}
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>

        {{ $devoluciones->links() }}
    </x-page>
</x-app-layout>
