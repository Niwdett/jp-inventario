@php
    $estadoActual = request('estado');
    $filtros = [
        '' => __('Todas'),
        'confirmada' => __('Confirmadas'),
        'anulada' => __('Anuladas'),
    ];
@endphp

<x-app-layout>
    <x-page :title="auth()->user()->esEmpleado() ? __('Mis ventas') : __('Ventas')">
        <x-slot name="actions">
            <x-button :href="route('ventas.create')">
                <x-icon name="mas" class="size-4" />
                {{ __('Registrar venta') }}
            </x-button>
        </x-slot>

        <div class="flex flex-wrap gap-1 rounded-lg border border-line bg-surface p-1 text-sm shadow-xs sm:w-fit">
            @foreach ($filtros as $valor => $etiqueta)
                <a href="{{ route('ventas.index', $valor ? ['estado' => $valor] : []) }}"
                   @class([
                       'rounded-md px-3 py-1.5 font-medium transition-colors',
                       'bg-primary-600 text-white' => (string) $estadoActual === (string) $valor,
                       'text-ink-soft hover:bg-surface-sunken' => (string) $estadoActual !== (string) $valor,
                   ])>{{ $etiqueta }}</a>
            @endforeach
        </div>

        <x-card flush>
            <x-table stack>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Número') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Cliente') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Vendedor') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Método') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Total') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Estado') }}</th>
                </x-slot>

                @forelse ($ventas as $venta)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3">
                            <a href="{{ route('ventas.show', $venta) }}"
                               class="font-mono text-sm font-medium text-primary-700 hover:text-primary-800">{{ $venta->numero }}</a>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft" data-label="{{ __('Fecha') }}">{{ $venta->fecha_venta->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3 text-ink" data-label="{{ __('Cliente') }}">{{ $venta->cliente?->nombre ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-soft" data-label="{{ __('Vendedor') }}">{{ $venta->usuario->name }}</td>
                        <td class="px-5 py-3 text-ink-soft" data-label="{{ __('Método') }}">{{ $venta->metodo_pago->label() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink" data-label="{{ __('Total') }}">{{ number_format((float) $venta->total, 2) }}</td>
                        <td class="px-5 py-3" data-label="{{ __('Estado') }}">
                            @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                                <x-badge variant="danger">{{ __('Anulada') }}</x-badge>
                            @elseif ($venta->entregada_at)
                                <x-badge variant="success">{{ __('Entregada') }}</x-badge>
                            @else
                                <x-badge variant="info">{{ __('Confirmada') }}</x-badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    @if ($estadoActual)
                        <x-table-empty :colspan="7" icon="buscar"
                                       :title="__('No hay ventas :estado', ['estado' => \Illuminate\Support\Str::lower($filtros[$estadoActual] ?? '')])">
                            <x-slot:actions>
                                <x-button variant="secondary" size="sm" :href="route('ventas.index')">{{ __('Ver todas') }}</x-button>
                            </x-slot:actions>
                        </x-table-empty>
                    @else
                        <x-table-empty :colspan="7" icon="ventas" :title="__('Aún no hay ventas registradas')">
                            {{ __('Registra la primera venta para llevar el control de tu inventario y tus ingresos.') }}
                            <x-slot:actions>
                                <x-button size="sm" :href="route('ventas.create')">
                                    <x-icon name="mas" class="size-4" />
                                    {{ __('Registrar venta') }}
                                </x-button>
                            </x-slot:actions>
                        </x-table-empty>
                    @endif
                @endforelse
            </x-table>
        </x-card>

        {{ $ventas->links() }}
    </x-page>
</x-app-layout>
