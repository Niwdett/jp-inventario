<x-app-layout>
    <x-page :title="__('Ajustes de inventario')">
        <x-slot name="actions">
            <x-button :href="route('admin.inventario.ajustes.create')">
                <x-icon name="ajuste" class="size-4" />
                {{ __('Ajustar inventario') }}
            </x-button>
        </x-slot>

        @include('admin.inventario._nav')
        @include('admin.inventario._buscador')

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Variante') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Antes') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Después') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Motivo') }}</th>
                </x-slot>

                @forelse ($ajustes as $ajuste)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $ajuste->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3">
                            <span class="text-ink">{{ $ajuste->variante->producto->nombre }}</span>
                            <span class="text-ink-faint">— {{ $ajuste->variante->etiqueta() }}</span>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $ajuste->cantidad_anterior }}</td>
                        <td class="px-5 py-3 text-right font-medium tabular-nums text-ink">{{ $ajuste->cantidad_nueva }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $ajuste->motivo ?: '—' }}</td>
                    </tr>
                @empty
                    @if ($buscar !== '')
                        <x-table-empty :colspan="5" icon="buscar" :title="__('Ningún ajuste coincide con «:buscar»', ['buscar' => $buscar])">
                            <x-slot:actions>
                                <x-button variant="secondary" size="sm" :href="route('admin.inventario.ajustes.index')">{{ __('Ver todos') }}</x-button>
                            </x-slot:actions>
                        </x-table-empty>
                    @else
                        <x-table-empty :colspan="5" icon="ajuste" :title="__('Aún no hay ajustes registrados')">
                            {{ __('Usa un ajuste cuando el conteo físico no coincida con el stock del sistema.') }}
                            <x-slot:actions>
                                <x-button size="sm" :href="route('admin.inventario.ajustes.create')">
                                    <x-icon name="ajuste" class="size-4" />
                                    {{ __('Ajustar inventario') }}
                                </x-button>
                            </x-slot:actions>
                        </x-table-empty>
                    @endif
                @endforelse
            </x-table>
        </x-card>

        {{ $ajustes->links() }}
    </x-page>
</x-app-layout>
