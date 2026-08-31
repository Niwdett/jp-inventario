@php
    $etiquetas = [
        'alta' => __('Alta del producto'),
        'estado' => __('Estado'),
        'nombre' => __('Nombre'),
        'marca' => __('Marca'),
        'precio_referencia' => __('Precio de referencia'),
        'umbral_stock_bajo' => __('Umbral de stock bajo'),
        'proveedor' => __('Proveedor'),
    ];
@endphp

<x-app-layout>
    <x-page :title="__('Historial del producto')"
            :subtitle="$producto->nombre.' · '.$producto->codigo_interno">
        <x-slot name="actions">
            <x-button variant="secondary" :href="route('admin.productos.show', $producto)">
                <x-icon name="arrow-left" class="size-4" />
                {{ __('Volver al producto') }}
            </x-button>
        </x-slot>

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Campo') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Antes') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Después') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Usuario') }}</th>
                </x-slot>

                @forelse ($entradas as $entrada)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $entrada->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-3 text-ink">{{ $etiquetas[$entrada->campo] ?? $entrada->campo }}</td>
                        <td class="px-5 py-3 text-ink-faint">{{ $entrada->valor_anterior ?? '—' }}</td>
                        <td class="px-5 py-3 font-medium text-ink">{{ $entrada->valor_nuevo ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $entrada->usuario?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" icon="historial" :title="__('Sin movimientos registrados')">
                        {{ __('Aquí quedará el rastro de cada cambio en los datos del producto.') }}
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>

        {{ $entradas->links() }}
    </x-page>
</x-app-layout>
