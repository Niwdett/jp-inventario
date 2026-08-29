<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Devoluciones') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Venta') }}</th>
                            <th class="px-6 py-3">{{ __('Cliente') }}</th>
                            <th class="px-6 py-3">{{ __('Estado') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Saldo generado') }}</th>
                            <th class="px-6 py-3">{{ __('Validó') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($devoluciones as $devolucion)
                            <tr>
                                <td class="px-6 py-4">{{ $devolucion->fecha->format('Y-m-d') }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('ventas.show', $devolucion->venta) }}" class="text-indigo-600 hover:text-indigo-900 font-mono">{{ $devolucion->venta->numero }}</a>
                                </td>
                                <td class="px-6 py-4">{{ $devolucion->venta->cliente?->nombre ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($devolucion->estado === \App\Enums\EstadoDevolucion::Validada)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">{{ __('Validada') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">{{ __('Rechazada') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $devolucion->saldo_generado, 2) }}</td>
                                <td class="px-6 py-4">{{ $devolucion->usuario?->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no se han registrado devoluciones.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $devoluciones->links() }}
        </div>
    </div>
</x-app-layout>
