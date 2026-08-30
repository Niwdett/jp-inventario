<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Cliente') }}: {{ $cliente->nombre }}
            </h2>
            <div class="flex items-center gap-4">
                @can('update', $cliente)
                    <a href="{{ route('admin.clientes.edit', $cliente) }}" class="text-sm text-indigo-600 hover:text-indigo-900 underline">{{ __('Editar') }}</a>
                @endcan
                <a href="{{ route('admin.clientes.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('← Volver') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-2 text-sm">
                    <dt class="text-gray-500">{{ __('Teléfono') }}</dt><dd class="sm:col-span-2">{{ $cliente->telefono ?? '—' }}</dd>
                    <dt class="text-gray-500">{{ __('Cédula') }}</dt><dd class="sm:col-span-2 font-mono">{{ $cliente->cedula ?? '—' }}</dd>
                    <dt class="text-gray-500">{{ __('Saldo a favor') }}</dt>
                    <dd class="sm:col-span-2 font-semibold {{ (float) $cliente->saldo_favor > 0 ? 'text-green-700' : 'text-gray-800' }}">
                        {{ number_format((float) $cliente->saldo_favor, 2) }}
                    </dd>
                </dl>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-3 font-semibold text-gray-800 border-b border-gray-100">{{ __('Ventas a crédito con saldo pendiente') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Venta') }}</th>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Deuda inicial') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Pendiente') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cliente->ventasACredito as $venta)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('ventas.show', $venta) }}" class="text-indigo-600 hover:text-indigo-900 font-mono">{{ $venta->numero }}</a>
                                </td>
                                <td class="px-6 py-4">{{ $venta->fecha_venta->format('Y-m-d') }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $venta->credito_monto, 2) }}</td>
                                <td class="px-6 py-4 text-right font-semibold">{{ number_format((float) $venta->credito_saldo_pendiente, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-500">{{ __('Sin deudas pendientes.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <h3 class="px-6 py-3 font-semibold text-gray-800 border-b border-gray-100">{{ __('Movimientos de saldo a favor') }}</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Tipo') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Monto') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cliente->saldoFavorMovimientos as $movimiento)
                            <tr>
                                <td class="px-6 py-4">{{ $movimiento->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4">{{ $movimiento->tipo->label() }}</td>
                                <td class="px-6 py-4 text-right {{ (float) $movimiento->monto < 0 ? 'text-red-600' : 'text-green-700' }}">
                                    {{ number_format((float) $movimiento->monto, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">{{ __('Sin movimientos de saldo a favor.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
