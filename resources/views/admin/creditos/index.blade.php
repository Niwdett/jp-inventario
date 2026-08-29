<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Créditos por cobrar') }}</h2>
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
                            <th class="px-6 py-3">{{ __('Venta') }}</th>
                            <th class="px-6 py-3">{{ __('Cliente') }}</th>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Deuda inicial') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Pendiente') }}</th>
                            <th class="px-6 py-3">{{ __('Mora') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($ventas as $venta)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('ventas.show', $venta) }}" class="text-indigo-600 hover:text-indigo-900 font-mono">{{ $venta->numero }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($venta->cliente)
                                        <a href="{{ route('admin.clientes.show', $venta->cliente) }}" class="text-indigo-600 hover:text-indigo-900">{{ $venta->cliente->nombre }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $venta->fecha_venta->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $venta->credito_monto, 2) }}</td>
                                <td class="px-6 py-4 text-right font-semibold">{{ number_format((float) $venta->credito_saldo_pendiente, 2) }}</td>
                                <td class="px-6 py-4">
                                    @if ($venta->fecha_venta->diffInDays(now()) > \App\Models\Cliente::DIAS_MORA)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs">{{ __('En mora') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">{{ __('Al día') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">{{ __('No hay créditos pendientes de cobro.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $ventas->links() }}
        </div>
    </div>
</x-app-layout>
