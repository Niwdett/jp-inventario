<x-app-layout>
    <x-page :title="__('Clientes')">
        <x-slot name="actions">
            <x-button :href="route('admin.clientes.create')">
                <x-icon name="mas" class="size-4" />
                {{ __('Nuevo cliente') }}
            </x-button>
        </x-slot>

        <x-card flush>
            <x-table stack>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Nombre') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Teléfono') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Cédula') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Saldo a favor') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Créditos abiertos') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Estado') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                </x-slot>

                @forelse ($clientes as $cliente)
                    <tr @class(['transition-colors', 'hover:bg-surface-sunken/60' => ! $cliente->trashed(), 'text-ink-faint' => $cliente->trashed()])>
                        <td class="px-5 py-3">
                            @if ($cliente->trashed())
                                <span class="font-medium">{{ $cliente->nombre }}</span>
                            @else
                                <a href="{{ route('admin.clientes.show', $cliente) }}" class="font-medium text-primary-700 hover:text-primary-800">{{ $cliente->nombre }}</a>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-soft" data-label="{{ __('Teléfono') }}">{{ $cliente->telefono ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-ink-soft" data-label="{{ __('Cédula') }}">{{ $cliente->cedula ?? '—' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ (float) $cliente->saldo_favor > 0 ? 'text-success-700' : 'text-ink-soft' }}" data-label="{{ __('Saldo a favor') }}">
                            <x-money :value="$cliente->saldo_favor" />
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft" data-label="{{ __('Créditos abiertos') }}">{{ $cliente->ventas_a_credito_count }}</td>
                        <td class="px-5 py-3" data-label="{{ __('Estado') }}">
                            @if ($cliente->trashed())
                                <x-badge>{{ __('Eliminado') }}</x-badge>
                            @else
                                <x-badge variant="success">{{ __('Activo') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1 max-sm:justify-start">
                                @if ($cliente->trashed())
                                    @can('restore', $cliente)
                                        <form method="POST" action="{{ route('admin.clientes.restore', $cliente) }}">
                                            @csrf @method('PATCH')
                                            <x-icon-button icon="restaurar" :label="__('Restaurar')" />
                                        </form>
                                    @else
                                        <span class="text-ink-faint">—</span>
                                    @endcan
                                @else
                                    @can('update', $cliente)
                                        <x-icon-button icon="editar" :label="__('Editar')" :href="route('admin.clientes.edit', $cliente)" />
                                    @endcan
                                    @can('delete', $cliente)
                                        <form method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}"
                                              data-confirm="{{ __('Se eliminará al cliente «:nombre». Podrás restaurarlo después.', ['nombre' => $cliente->nombre]) }}"
                                              data-confirm-title="{{ __('Eliminar cliente') }}"
                                              data-confirm-label="{{ __('Eliminar') }}">
                                            @csrf @method('DELETE')
                                            <x-icon-button icon="eliminar" :label="__('Eliminar')" variant="danger" />
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="7" icon="clientes" :title="__('Aún no hay clientes')">
                        {{ __('Crea el primero para poder venderle a crédito o registrar su saldo a favor.') }}
                        <x-slot:actions>
                            <x-button size="sm" :href="route('admin.clientes.create')">
                                <x-icon name="mas" class="size-4" />
                                {{ __('Nuevo cliente') }}
                            </x-button>
                        </x-slot:actions>
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
