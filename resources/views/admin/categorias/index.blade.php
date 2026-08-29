<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Categorías') }}
            </h2>
            <a href="{{ route('admin.categorias.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nueva categoría') }}
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
                            <th class="px-6 py-3">{{ __('Prefijo') }}</th>
                            <th class="px-6 py-3">{{ __('Productos') }}</th>
                            <th class="px-6 py-3">{{ __('Estado') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categorias as $categoria)
                            <tr class="{{ $categoria->trashed() ? 'bg-gray-50 text-gray-400' : '' }}">
                                <td class="px-6 py-4 font-medium">{{ $categoria->nombre }}</td>
                                <td class="px-6 py-4 font-mono">{{ $categoria->prefijo_codigo }}</td>
                                <td class="px-6 py-4">{{ $categoria->productos_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($categoria->trashed())
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs">{{ __('Eliminada') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">{{ __('Activa') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($categoria->trashed())
                                            <form method="POST" action="{{ route('admin.categorias.restore', $categoria) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-indigo-600 hover:text-indigo-900">{{ __('Restaurar') }}</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.categorias.edit', $categoria) }}"
                                               class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>

                                            <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}"
                                                  onsubmit="return confirm('¿Eliminar la categoría {{ $categoria->nombre }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    {{ __('Aún no hay categorías. Crea la primera para poder registrar productos.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
