<x-app-layout>
    <x-page :title="__('Usuarios')">
        <x-slot name="actions">
            <x-button :href="route('admin.usuarios.create')">
                <x-icon name="mas" class="size-4" />
                {{ __('Nuevo usuario') }}
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
                    <th class="px-5 py-3 font-medium">{{ __('Correo') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Rol') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Estado') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                </x-slot>

                @foreach ($usuarios as $usuario)
                    <tr @class(['transition-colors', 'hover:bg-surface-sunken/60' => ! $usuario->trashed(), 'text-ink-faint' => $usuario->trashed()])>
                        <td class="px-5 py-3">
                            <span class="font-medium {{ $usuario->trashed() ? 'text-ink-faint' : 'text-ink' }}">{{ $usuario->name }}</span>
                            @if ($usuario->is(auth()->user()))
                                <span class="text-xs text-ink-faint">({{ __('tú') }})</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-soft">{{ $usuario->email }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $usuario->rol->label() }}</td>
                        <td class="px-5 py-3">
                            @if ($usuario->trashed())
                                <x-badge>{{ __('Inactivo') }}</x-badge>
                            @else
                                <x-badge variant="success">{{ __('Activo') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                @if ($usuario->trashed())
                                    <form method="POST" action="{{ route('admin.usuarios.restore', $usuario) }}">
                                        @csrf @method('PATCH')
                                        <x-icon-button icon="restaurar" :label="__('Reactivar')" />
                                    </form>
                                @else
                                    <x-icon-button icon="editar" :label="__('Editar')" :href="route('admin.usuarios.edit', $usuario)" />
                                    @unless ($usuario->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}"
                                              data-confirm="{{ __('«:nombre» dejará de poder iniciar sesión. Podrás reactivarlo después.', ['nombre' => $usuario->name]) }}"
                                              data-confirm-title="{{ __('Desactivar usuario') }}"
                                              data-confirm-label="{{ __('Desactivar') }}">
                                            @csrf @method('DELETE')
                                            <x-icon-button icon="eliminar" :label="__('Desactivar')" variant="danger" />
                                        </form>
                                    @endunless
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </x-page>
</x-app-layout>
