<x-app-layout>
    <x-page :title="__('Movimientos de inventario')">
        @include('admin.inventario._nav')

        <x-card>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="min-w-56 flex-1">
                    <x-input-label for="variante_id" :value="__('Filtrar por variante')" />
                    <x-select-input id="variante_id" name="variante_id" class="mt-1.5"
                        :options="$variantes" :selected="$varianteSeleccionada"
                        :placeholder="__('— Todas —')" />
                </div>
                <x-button>
                    <x-icon name="filtro" class="size-4" />
                    {{ __('Filtrar') }}
                </x-button>
                @if ($varianteSeleccionada)
                    <x-button variant="ghost" :href="route('admin.inventario.movimientos.index')">{{ __('Limpiar') }}</x-button>
                @endif
            </form>
        </x-card>

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Variante') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Tipo') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Cantidad') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Stock resultante') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Usuario') }}</th>
                </x-slot>

                @forelse ($movimientos as $movimiento)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $movimiento->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3">
                            <span class="text-ink">{{ $movimiento->variante->producto->nombre }}</span>
                            <span class="text-ink-faint">— {{ $movimiento->variante->etiqueta() }}</span>
                        </td>
                        <td class="px-5 py-3 text-ink-soft">{{ $movimiento->tipo->label() }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ $movimiento->cantidad < 0 ? 'text-danger-600' : 'text-success-700' }}">
                            {{ $movimiento->cantidad > 0 ? '+' : '' }}{{ $movimiento->cantidad }}
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $movimiento->stock_resultante }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $movimiento->usuario?->name ?? '—' }}</td>
                    </tr>
                @empty
                    @if ($varianteSeleccionada)
                        <x-table-empty :colspan="6" icon="buscar" :title="__('Esta variante no tiene movimientos')">
                            <x-slot:actions>
                                <x-button variant="secondary" size="sm" :href="route('admin.inventario.movimientos.index')">{{ __('Ver todos') }}</x-button>
                            </x-slot:actions>
                        </x-table-empty>
                    @else
                        <x-table-empty :colspan="6" icon="movimientos" :title="__('Sin movimientos de inventario')">
                            {{ __('Las entradas, ajustes y ventas quedarán registrados aquí automáticamente.') }}
                        </x-table-empty>
                    @endif
                @endforelse
            </x-table>
        </x-card>

        {{ $movimientos->links() }}
    </x-page>
</x-app-layout>
