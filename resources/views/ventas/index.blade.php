@php
    $estadoActual = request('estado');
    $filtros = [
        '' => __('Todas'),
        'confirmada' => __('Confirmadas'),
        'anulada' => __('Anuladas'),
    ];

    // Parámetros de búsqueda que deben viajar con los chips de estado y la paginación.
    $paramsBusqueda = array_filter(request()->only(['buscar', 'desde', 'hasta']), fn ($v) => $v !== null && $v !== '');
    $hayBusqueda = $paramsBusqueda !== [];

    // Accesos rápidos de rango de fechas.
    $hoy = \Illuminate\Support\Carbon::today();
    $presetsRango = [
        __('Hoy') => ['desde' => $hoy->toDateString(), 'hasta' => $hoy->toDateString()],
        __('Ayer') => ['desde' => $hoy->copy()->subDay()->toDateString(), 'hasta' => $hoy->copy()->subDay()->toDateString()],
        __('Esta semana') => ['desde' => $hoy->copy()->startOfWeek()->toDateString(), 'hasta' => $hoy->copy()->endOfWeek()->toDateString()],
        __('Este mes') => ['desde' => $hoy->copy()->startOfMonth()->toDateString(), 'hasta' => $hoy->copy()->endOfMonth()->toDateString()],
    ];
    $paramsBase = array_filter(request()->only(['buscar', 'estado']), fn ($v) => $v !== null && $v !== '');
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
                <a href="{{ route('ventas.index', array_merge($paramsBusqueda, $valor ? ['estado' => $valor] : [])) }}"
                   @class([
                       'rounded-md px-3 py-1.5 font-medium transition-colors',
                       'bg-primary-600 text-white' => (string) $estadoActual === (string) $valor,
                       'text-ink-soft hover:bg-surface-sunken' => (string) $estadoActual !== (string) $valor,
                   ])>{{ $etiqueta }}</a>
            @endforeach
        </div>

        <x-card>
            <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap items-end gap-3">
                @if ($estadoActual)
                    <input type="hidden" name="estado" value="{{ $estadoActual }}">
                @endif

                <div class="min-w-56 flex-1">
                    <x-input-label for="buscar" :value="__('Buscar venta o cliente')" />
                    <x-text-input id="buscar" name="buscar" type="search" class="mt-1.5 w-full"
                                  :value="request('buscar')" placeholder="{{ __('Nº de venta o nombre del cliente…') }}" />
                </div>
                <div>
                    <x-input-label for="desde" :value="__('Desde')" />
                    <x-text-input id="desde" name="desde" type="date" class="mt-1.5" :value="request('desde')" />
                </div>
                <div>
                    <x-input-label for="hasta" :value="__('Hasta')" />
                    <x-text-input id="hasta" name="hasta" type="date" class="mt-1.5" :value="request('hasta')" />
                </div>

                <x-button>
                    <x-icon name="filtro" class="size-4" />
                    {{ __('Filtrar') }}
                </x-button>
                @if ($hayBusqueda)
                    <x-button variant="ghost" :href="route('ventas.index', $estadoActual ? ['estado' => $estadoActual] : [])">
                        {{ __('Limpiar') }}
                    </x-button>
                @endif

                <x-input-error :messages="$errors->get('desde')" class="w-full" />
                <x-input-error :messages="$errors->get('hasta')" class="w-full" />
            </form>

            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-line pt-3">
                <span class="mr-0.5 text-xs text-ink-faint">{{ __('Rápido:') }}</span>
                @foreach ($presetsRango as $etiqueta => $rango)
                    @php($activo = request('desde') === $rango['desde'] && request('hasta') === $rango['hasta'])
                    <a href="{{ route('ventas.index', array_merge($paramsBase, $rango)) }}"
                       @class([
                           'inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-medium transition-colors',
                           'border-primary-600 bg-primary-600 text-white' => $activo,
                           'border-line bg-surface text-ink-soft hover:bg-surface-sunken' => ! $activo,
                       ])>{{ $etiqueta }}</a>
                @endforeach
            </div>
        </x-card>

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
                            @if ($venta->lineas->isNotEmpty())
                                <span class="mt-0.5 block text-xs text-ink-faint">{{ $venta->resumenProductos() }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft" data-label="{{ __('Fecha') }}">{{ $venta->fecha_venta->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3 text-ink" data-label="{{ __('Cliente') }}">{{ $venta->cliente?->nombre ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-soft" data-label="{{ __('Vendedor') }}">{{ $venta->usuario->name }}</td>
                        <td class="px-5 py-3 text-ink-soft" data-label="{{ __('Método') }}">{{ $venta->metodo_pago->label() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink" data-label="{{ __('Total') }}"><x-money :value="$venta->total" /></td>
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
                    @if ($hayBusqueda || $estadoActual)
                        <x-table-empty :colspan="7" icon="buscar" :title="__('Ninguna venta coincide con los filtros')">
                            <x-slot:actions>
                                <x-button variant="secondary" size="sm" :href="route('ventas.index')">{{ __('Quitar filtros') }}</x-button>
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
