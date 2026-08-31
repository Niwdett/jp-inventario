<x-app-layout>
    <x-page :title="__('Alertas de stock bajo')">
        @include('admin.inventario._nav')

        @if ($variantes->isEmpty())
            <x-card>
                <x-empty-state icon="check" tone="positive" :title="__('Todo el inventario está por encima de su umbral')">
                    {{ __('Ninguna variante necesita reposición ahora mismo.') }}
                </x-empty-state>
            </x-card>
        @else
            <x-alert variant="warning">
                {{ trans_choice('{1} :count variante necesita reposición.|[2,*] :count variantes necesitan reposición.', $variantes->count(), ['count' => $variantes->count()]) }}
            </x-alert>

            <x-card flush>
                <x-table>
                    <x-slot name="head">
                        <th class="px-5 py-3 font-medium">{{ __('Producto') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Variante') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Stock') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Umbral') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                    </x-slot>

                    @foreach ($variantes as $variante)
                        <tr class="transition-colors hover:bg-surface-sunken/60">
                            <td class="px-5 py-3">
                                <span class="text-ink">{{ $variante->producto->nombre }}</span>
                                <span class="ml-1 font-mono text-xs text-ink-faint">{{ $variante->producto->codigo_interno }}</span>
                            </td>
                            <td class="px-5 py-3 text-ink-soft">{{ $variante->etiqueta() }}</td>
                            <td class="px-5 py-3 text-right">
                                <x-badge variant="warning" class="tabular-nums">{{ $variante->stock }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $variante->producto->umbral_stock_bajo }}</td>
                            <td class="px-5 py-3 text-right">
                                <x-button variant="secondary" size="sm"
                                          :href="route('admin.inventario.entradas.create', ['variante_id' => $variante->id])">
                                    <x-icon name="entrada" class="size-4" />
                                    {{ __('Registrar entrada') }}
                                </x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    </x-page>
</x-app-layout>
