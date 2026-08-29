<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @isset($empleado)
                <div class="grid sm:grid-cols-2 gap-4">
                    <x-reportes.tarjeta :titulo="__('Mis ventas de hoy')" :valor="number_format($empleado['ventas_hoy'])" />
                    <x-reportes.tarjeta :titulo="__('Total vendido hoy')" :valor="number_format((float) $empleado['total_hoy'], 2)" />
                </div>
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <a href="{{ route('ventas.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Registrar venta') }}
                    </a>
                    <a href="{{ route('ventas.index') }}" class="ms-3 text-sm text-indigo-600 hover:text-indigo-900">{{ __('Ver mis ventas') }}</a>
                </div>
            @endisset

            @isset($admin)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('admin.reportes.ventas', ['preset' => 'hoy']) }}">
                        <x-reportes.tarjeta :titulo="__('Ventas de hoy')" :valor="number_format($admin['ventas_hoy'])"
                                            :detalle="number_format((float) $admin['total_hoy'], 2)" class="hover:ring-1 hover:ring-indigo-300 transition" />
                    </a>
                    <a href="{{ route('admin.reportes.ventas', ['preset' => 'mes']) }}">
                        <x-reportes.tarjeta :titulo="__('Ventas del mes')" :valor="number_format($admin['ventas_mes'])"
                                            :detalle="number_format((float) $admin['total_mes'], 2)" class="hover:ring-1 hover:ring-indigo-300 transition" />
                    </a>
                    <a href="{{ route('admin.reportes.ganancias', ['preset' => 'mes']) }}">
                        <x-reportes.tarjeta :titulo="__('Ganancia bruta del mes')" :valor="number_format((float) $admin['ganancia_mes'], 2)"
                                            :tono="(float) $admin['ganancia_mes'] < 0 ? 'negativo' : 'positivo'" class="hover:ring-1 hover:ring-indigo-300 transition" />
                    </a>
                    <a href="{{ route('admin.inventario.alertas.index') }}">
                        <x-reportes.tarjeta :titulo="__('Variantes en stock bajo')" :valor="number_format($admin['variantes_stock_bajo'])"
                                            :tono="$admin['variantes_stock_bajo'] > 0 ? 'alerta' : 'neutral'" class="hover:ring-1 hover:ring-indigo-300 transition" />
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <a href="{{ route('admin.creditos.index') }}">
                        <x-reportes.tarjeta :titulo="__('Crédito por cobrar')" :valor="number_format((float) $admin['credito_por_cobrar'], 2)"
                                            class="hover:ring-1 hover:ring-indigo-300 transition" />
                    </a>
                    <a href="{{ route('admin.creditos.index') }}">
                        <x-reportes.tarjeta :titulo="__('Clientes en mora')" :valor="number_format($admin['clientes_en_mora'])"
                                            :tono="$admin['clientes_en_mora'] > 0 ? 'negativo' : 'neutral'" class="hover:ring-1 hover:ring-indigo-300 transition" />
                    </a>
                    <x-reportes.tarjeta :titulo="__('Saldo a favor de clientes')" :valor="number_format((float) $admin['saldo_favor_clientes'], 2)" />
                </div>

                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <h3 class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">{{ __('Productos más vendidos del mes') }}</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">{{ __('Producto') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Unidades') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Ingreso') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($admin['top_productos'] as $fila)
                                <tr>
                                    <td class="px-6 py-3">
                                        {{ $fila->nombre }}
                                        <span class="font-mono text-xs text-gray-400">{{ $fila->codigo_interno }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right">{{ number_format($fila->unidades) }}</td>
                                    <td class="px-6 py-3 text-right">{{ number_format((float) $fila->ingreso, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no hay ventas este mes.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endisset

        </div>
    </div>
</x-app-layout>
