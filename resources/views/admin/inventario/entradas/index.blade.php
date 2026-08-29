<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Entradas de mercancía') }}</h2>
            <a href="{{ route('admin.inventario.entradas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Registrar entrada') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('admin.inventario._nav')

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Variante') }}</th>
                            <th class="px-6 py-3">{{ __('Cantidad') }}</th>
                            <th class="px-6 py-3">{{ __('Costo unitario') }}</th>
                            <th class="px-6 py-3">{{ __('Proveedor') }}</th>
                            <th class="px-6 py-3">{{ __('Registró') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($entradas as $entrada)
                            <tr>
                                <td class="px-6 py-4">{{ $entrada->fecha->format('Y-m-d') }}</td>
                                <td class="px-6 py-4">
                                    {{ $entrada->variante->producto->nombre }}
                                    <span class="text-gray-400">— {{ $entrada->variante->etiqueta() }}</span>
                                </td>
                                <td class="px-6 py-4">+{{ $entrada->cantidad }}</td>
                                <td class="px-6 py-4">{{ number_format((float) $entrada->costo_unitario, 4) }}</td>
                                <td class="px-6 py-4">{{ $entrada->proveedor ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $entrada->usuario->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no hay entradas registradas.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $entradas->links() }}</div>
        </div>
    </div>
</x-app-layout>
