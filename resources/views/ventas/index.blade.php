<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ auth()->user()->esEmpleado() ? __('Mis ventas') : __('Ventas') }}
            </h2>
            <a href="{{ route('ventas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Registrar venta') }}
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

            <div class="flex gap-2 text-sm">
                <a href="{{ route('ventas.index') }}"
                   class="px-3 py-1 rounded-md {{ ! request('estado') ? 'bg-gray-800 text-white' : 'bg-white border border-gray-300 text-gray-600' }}">{{ __('Todas') }}</a>
                <a href="{{ route('ventas.index', ['estado' => 'confirmada']) }}"
                   class="px-3 py-1 rounded-md {{ request('estado') === 'confirmada' ? 'bg-gray-800 text-white' : 'bg-white border border-gray-300 text-gray-600' }}">{{ __('Confirmadas') }}</a>
                <a href="{{ route('ventas.index', ['estado' => 'anulada']) }}"
                   class="px-3 py-1 rounded-md {{ request('estado') === 'anulada' ? 'bg-gray-800 text-white' : 'bg-white border border-gray-300 text-gray-600' }}">{{ __('Anuladas') }}</a>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Número') }}</th>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Cliente') }}</th>
                            <th class="px-6 py-3">{{ __('Vendedor') }}</th>
                            <th class="px-6 py-3">{{ __('Método') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Total') }}</th>
                            <th class="px-6 py-3">{{ __('Estado') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($ventas as $venta)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono">
                                    <a href="{{ route('ventas.show', $venta) }}" class="text-indigo-600 hover:text-indigo-900">{{ $venta->numero }}</a>
                                </td>
                                <td class="px-6 py-4">{{ $venta->fecha_venta->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4">{{ $venta->cliente?->nombre ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $venta->usuario->name }}</td>
                                <td class="px-6 py-4">{{ $venta->metodo_pago->label() }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $venta->total, 2) }}</td>
                                <td class="px-6 py-4">
                                    @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs">{{ __('Anulada') }}</span>
                                    @elseif ($venta->entregada_at)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">{{ __('Entregada') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs">{{ __('Confirmada') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no hay ventas registradas.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $ventas->links() }}</div>
        </div>
    </div>
</x-app-layout>
