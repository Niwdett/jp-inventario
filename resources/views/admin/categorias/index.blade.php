<x-app-layout>
    <x-page :title="__('Categorías')">
        <x-slot name="actions">
            <x-button :href="route('admin.categorias.create')">
                <x-icon name="mas" class="size-4" />
                {{ __('Nueva categoría') }}
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
                    <th class="px-5 py-3 font-medium">{{ __('Nombre') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Prefijo') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Productos') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Estado') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                </x-slot>

                @forelse ($categorias as $categoria)
                    <tr @class(['transition-colors', 'hover:bg-surface-sunken/60' => ! $categoria->trashed(), 'text-ink-faint' => $categoria->trashed()])>
                        <td class="px-5 py-3 font-medium {{ $categoria->trashed() ? 'text-ink-faint' : 'text-ink' }}">{{ $categoria->nombre }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-ink-soft">{{ $categoria->prefijo_codigo }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $categoria->productos_count }}</td>
                        <td class="px-5 py-3">
                            @if ($categoria->trashed())
                                <x-badge>{{ __('Eliminada') }}</x-badge>
                            @else
                                <x-badge variant="success">{{ __('Activa') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @if ($categoria->trashed())
                                    <form method="POST" action="{{ route('admin.categorias.restore', $categoria) }}">
                                        @csrf @method('PATCH')
                                        <x-icon-button icon="restaurar" :label="__('Restaurar')" />
                                    </form>
                                @else
                                    <x-icon-button icon="editar" :label="__('Editar')" :href="route('admin.categorias.edit', $categoria)" />
                                    <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}"
                                          onsubmit="return confirm('¿Eliminar la categoría {{ $categoria->nombre }}?')">
                                        @csrf @method('DELETE')
                                        <x-icon-button icon="eliminar" :label="__('Eliminar')" variant="danger" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-ink-faint">
                            {{ __('Aún no hay categorías. Crea la primera para poder registrar productos.') }}
                        </td>
                    </tr>
                @endforelse
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
