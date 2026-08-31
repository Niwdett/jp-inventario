<x-app-layout>
    <x-page :title="__('Productos')">
        <x-slot name="actions">
            <x-button :href="route('admin.productos.create')">
                <x-icon name="mas" class="size-4" />
                {{ __('Nuevo producto') }}
            </x-button>
        </x-slot>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Código') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Producto') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Categoría') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Precio ref.') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Stock') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                </x-slot>

                @forelse ($productos as $producto)
                    <tr @class([
                        'transition-colors',
                        'hover:bg-surface-sunken/60' => ! $producto->trashed(),
                        'text-ink-faint' => $producto->trashed(),
                    ])>
                        <td class="px-5 py-3 font-mono text-xs">{{ $producto->codigo_interno }}</td>
                        <td class="px-5 py-3">
                            <span class="font-medium {{ $producto->trashed() ? 'text-ink-faint' : 'text-ink' }}">{{ $producto->nombre }}</span>
                            @if ($producto->marca)
                                <span class="text-xs text-ink-faint">· {{ $producto->marca }}</span>
                            @endif
                            @if ($producto->trashed())
                                <x-badge class="ml-1">{{ __('Eliminado') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-soft">{{ $producto->categoria->nombre }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $producto->precio_referencia, 2) }}</td>
                        <td class="px-5 py-3 text-right">
                            <span class="tabular-nums">{{ (int) $producto->stock_total }}</span>
                            @if ($producto->variantes_bajas_count > 0 && ! $producto->trashed())
                                <x-badge variant="warning" class="ml-1">{{ __('Stock bajo') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @if ($producto->trashed())
                                    <form method="POST" action="{{ route('admin.productos.restore', $producto) }}">
                                        @csrf @method('PATCH')
                                        <x-icon-button icon="restaurar" :label="__('Restaurar')" />
                                    </form>
                                @else
                                    <x-icon-button icon="ver" :label="__('Ver')" :href="route('admin.productos.show', $producto)" />
                                    <x-icon-button icon="editar" :label="__('Editar')" :href="route('admin.productos.edit', $producto)" />
                                    <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}"
                                          data-confirm="{{ __('Se eliminará «:nombre» y sus variantes. Podrás restaurarlo después.', ['nombre' => $producto->nombre]) }}"
                                          data-confirm-title="{{ __('Eliminar producto') }}"
                                          data-confirm-label="{{ __('Eliminar') }}">
                                        @csrf @method('DELETE')
                                        <x-icon-button icon="eliminar" :label="__('Eliminar')" variant="danger" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" icon="productos" :title="__('Aún no hay productos')">
                        {{ __('Registra el primer producto y sus variantes para empezar a controlar el stock.') }}
                        <x-slot:actions>
                            <x-button size="sm" :href="route('admin.productos.create')">
                                <x-icon name="mas" class="size-4" />
                                {{ __('Nuevo producto') }}
                            </x-button>
                        </x-slot:actions>
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>

        {{ $productos->links() }}
    </x-page>
</x-app-layout>
