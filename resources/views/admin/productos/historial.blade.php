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
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Historial de') }} {{ $producto->nombre }}
            <span class="ms-2 font-mono text-sm text-gray-500">{{ $producto->codigo_interno }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <a href="{{ route('admin.productos.show', $producto) }}" class="inline-block text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('← Volver al producto') }}
            </a>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Campo') }}</th>
                            <th class="px-6 py-3">{{ __('Antes') }}</th>
                            <th class="px-6 py-3">{{ __('Después') }}</th>
                            <th class="px-6 py-3">{{ __('Usuario') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($entradas as $entrada)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $entrada->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4">{{ $etiquetas[$entrada->campo] ?? $entrada->campo }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $entrada->valor_anterior ?? '—' }}</td>
                                <td class="px-6 py-4 font-medium">{{ $entrada->valor_nuevo ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $entrada->usuario?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">{{ __('Sin movimientos registrados.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $entradas->links() }}
        </div>
    </div>
</x-app-layout>
