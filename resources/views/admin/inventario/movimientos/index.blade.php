<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Movimientos de inventario') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('admin.inventario._nav')

            <form method="GET" class="flex items-end gap-3">
                <div class="flex-1">
                    <x-input-label for="variante_id" :value="__('Filtrar por variante')" />
                    <x-select-input id="variante_id" name="variante_id" class="mt-1 block w-full"
                        :options="$variantes" :selected="$varianteSeleccionada"
                        :placeholder="__('— Todas —')" />
                </div>
                <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                @if ($varianteSeleccionada)
                    <a href="{{ route('admin.inventario.movimientos.index') }}" class="text-sm text-gray-600 underline">{{ __('Limpiar') }}</a>
                @endif
            </form>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Variante') }}</th>
                            <th class="px-6 py-3">{{ __('Tipo') }}</th>
                            <th class="px-6 py-3">{{ __('Cantidad') }}</th>
                            <th class="px-6 py-3">{{ __('Stock resultante') }}</th>
                            <th class="px-6 py-3">{{ __('Usuario') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($movimientos as $movimiento)
                            <tr>
                                <td class="px-6 py-4">{{ $movimiento->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4">
                                    {{ $movimiento->variante->producto->nombre }}
                                    <span class="text-gray-400">— {{ $movimiento->variante->etiqueta() }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $movimiento->tipo->label() }}</td>
                                <td class="px-6 py-4 {{ $movimiento->cantidad < 0 ? 'text-red-600' : 'text-green-700' }}">
                                    {{ $movimiento->cantidad > 0 ? '+' : '' }}{{ $movimiento->cantidad }}
                                </td>
                                <td class="px-6 py-4">{{ $movimiento->stock_resultante }}</td>
                                <td class="px-6 py-4">{{ $movimiento->usuario?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">{{ __('Sin movimientos.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $movimientos->links() }}</div>
        </div>
    </div>
</x-app-layout>
