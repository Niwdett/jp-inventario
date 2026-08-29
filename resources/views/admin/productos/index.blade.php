<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Productos') }}
            </h2>
            <a href="{{ route('admin.productos.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Nuevo producto') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Código') }}</th>
                            <th class="px-6 py-3">{{ __('Producto') }}</th>
                            <th class="px-6 py-3">{{ __('Categoría') }}</th>
                            <th class="px-6 py-3">{{ __('Precio ref.') }}</th>
                            <th class="px-6 py-3">{{ __('Stock total') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($productos as $producto)
                            <tr class="{{ $producto->trashed() ? 'bg-gray-50 text-gray-400' : '' }}">
                                <td class="px-6 py-4 font-mono">{{ $producto->codigo_interno }}</td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $producto->nombre }}
                                    @if ($producto->marca)
                                        <span class="text-xs text-gray-400">· {{ $producto->marca }}</span>
                                    @endif
                                    @if ($producto->trashed())
                                        <span class="ms-1 inline-flex px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-xs">{{ __('Eliminado') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $producto->categoria->nombre }}</td>
                                <td class="px-6 py-4">{{ number_format((float) $producto->precio_referencia, 2) }}</td>
                                <td class="px-6 py-4">
                                    {{ (int) $producto->stock_total }}
                                    @if ($producto->variantes_bajas_count > 0 && ! $producto->trashed())
                                        <span class="ms-1 inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs">{{ __('Stock bajo') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($producto->trashed())
                                            <form method="POST" action="{{ route('admin.productos.restore', $producto) }}">
                                                @csrf @method('PATCH')
                                                <button class="text-indigo-600 hover:text-indigo-900">{{ __('Restaurar') }}</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.productos.show', $producto) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Ver') }}</a>
                                            <a href="{{ route('admin.productos.edit', $producto) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>
                                            <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}"
                                                  onsubmit="return confirm('¿Eliminar {{ $producto->nombre }}?')">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:text-red-900">{{ __('Eliminar') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no hay productos.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $productos->links() }}</div>
        </div>
    </div>
</x-app-layout>
