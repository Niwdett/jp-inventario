<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Usuarios') }}
            </h2>
            <a href="{{ route('admin.usuarios.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nuevo usuario') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Nombre') }}</th>
                            <th class="px-6 py-3">{{ __('Correo') }}</th>
                            <th class="px-6 py-3">{{ __('Rol') }}</th>
                            <th class="px-6 py-3">{{ __('Estado') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($usuarios as $usuario)
                            <tr class="{{ $usuario->trashed() ? 'bg-gray-50 text-gray-400' : '' }}">
                                <td class="px-6 py-4 font-medium">
                                    {{ $usuario->name }}
                                    @if ($usuario->is(auth()->user()))
                                        <span class="text-xs text-gray-400">({{ __('tú') }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $usuario->email }}</td>
                                <td class="px-6 py-4">{{ $usuario->rol->label() }}</td>
                                <td class="px-6 py-4">
                                    @if ($usuario->trashed())
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs">{{ __('Inactivo') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">{{ __('Activo') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($usuario->trashed())
                                            <form method="POST" action="{{ route('admin.usuarios.restore', $usuario) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-indigo-600 hover:text-indigo-900">{{ __('Reactivar') }}</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                                               class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>

                                            @unless ($usuario->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}"
                                                      onsubmit="return confirm('¿Desactivar a {{ $usuario->name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:text-red-900">{{ __('Desactivar') }}</button>
                                                </form>
                                            @endunless
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
