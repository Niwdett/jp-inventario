<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Venta') }} <span class="font-mono text-gray-500">{{ $venta->numero }}</span>
                @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                    <span class="ms-2 inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs align-middle">{{ __('Anulada') }}</span>
                @elseif ($venta->entregada_at)
                    <span class="ms-2 inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs align-middle">{{ __('Entregada') }}</span>
                @endif
            </h2>
            <a href="{{ route('ventas.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('← Volver') }}</a>
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
                    <dt class="text-gray-500">{{ __('Fecha') }}</dt><dd class="sm:col-span-2">{{ $venta->fecha_venta->format('Y-m-d H:i') }}</dd>
                    <dt class="text-gray-500">{{ __('Vendedor') }}</dt><dd class="sm:col-span-2">{{ $venta->usuario->name }}</dd>
                    <dt class="text-gray-500">{{ __('Cliente') }}</dt>
                    <dd class="sm:col-span-2">
                        @if ($venta->cliente)
                            <a href="{{ route('admin.clientes.show', $venta->cliente) }}" class="text-indigo-600 hover:text-indigo-900">{{ $venta->cliente->nombre }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="text-gray-500">{{ __('Método de pago') }}</dt><dd class="sm:col-span-2">{{ $venta->metodo_pago->label() }}</dd>
                    @if ((float) $venta->saldo_favor_aplicado > 0)
                        <dt class="text-gray-500">{{ __('Saldo a favor aplicado') }}</dt>
                        <dd class="sm:col-span-2">{{ number_format((float) $venta->saldo_favor_aplicado, 2) }}</dd>
                    @endif
                    <dt class="text-gray-500">{{ __('Entrega') }}</dt>
                    <dd class="sm:col-span-2">{{ $venta->entregada_at?->format('Y-m-d H:i') ?? __('Pendiente') }}</dd>
                    @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                        <dt class="text-gray-500">{{ __('Anulada por') }}</dt>
                        <dd class="sm:col-span-2">{{ $venta->anuladaPor?->name }} · {{ $venta->anulada_at?->format('Y-m-d H:i') }}</dd>
                        <dt class="text-gray-500">{{ __('Motivo') }}</dt><dd class="sm:col-span-2">{{ $venta->motivo_anulacion }}</dd>
                    @endif
                </dl>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Producto') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Cantidad') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Precio') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Desc. %') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Importe') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($venta->lineas as $linea)
                            <tr>
                                <td class="px-6 py-4">
                                    {{ $linea->variante->producto->nombre }}
                                    <span class="text-gray-400">— {{ $linea->variante->etiqueta() }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">{{ $linea->cantidad }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $linea->precio_unitario, 2) }}</td>
                                <td class="px-6 py-4 text-right">{{ $linea->descuento_porcentaje ? number_format((float) $linea->descuento_porcentaje, 2) : '—' }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $linea->importe_linea, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 text-sm">
                        <tr>
                            <td class="px-6 py-2 text-right text-gray-500" colspan="4">{{ __('Subtotal') }}</td>
                            <td class="px-6 py-2 text-right">{{ number_format((float) $venta->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-2 text-right text-gray-500" colspan="4">{{ __('Descuento') }}</td>
                            <td class="px-6 py-2 text-right">{{ number_format((float) $venta->descuento_total, 2) }}</td>
                        </tr>
                        <tr class="font-semibold text-gray-800">
                            <td class="px-6 py-2 text-right" colspan="4">{{ __('Total') }}</td>
                            <td class="px-6 py-2 text-right">{{ number_format((float) $venta->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($venta->esCredito())
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800">{{ __('Crédito') }}</h3>
                    <dl class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-2 text-sm">
                        <dt class="text-gray-500">{{ __('Deuda inicial') }}</dt>
                        <dd class="sm:col-span-2">{{ number_format((float) $venta->credito_monto, 2) }}</dd>
                        <dt class="text-gray-500">{{ __('Saldo pendiente') }}</dt>
                        <dd class="sm:col-span-2 font-semibold {{ (float) $venta->credito_saldo_pendiente > 0 ? 'text-amber-700' : 'text-green-700' }}">
                            {{ number_format((float) $venta->credito_saldo_pendiente, 2) }}
                            @if ((float) $venta->credito_saldo_pendiente <= 0) · {{ __('saldada') }} @endif
                        </dd>
                        @if ($venta->credito_autorizado_por)
                            <dt class="text-gray-500">{{ __('Autorizada en mora por') }}</dt>
                            <dd class="sm:col-span-2">{{ $venta->creditoAutorizadoPor?->name }}</dd>
                        @endif
                    </dl>

                    @if ($venta->abonos->isNotEmpty())
                        <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="text-left text-xs uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="py-2">{{ __('Fecha') }}</th>
                                    <th class="py-2">{{ __('Registró') }}</th>
                                    <th class="py-2 text-right">{{ __('Monto') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($venta->abonos as $abono)
                                    <tr>
                                        <td class="py-2">{{ $abono->fecha->format('Y-m-d') }}</td>
                                        <td class="py-2">{{ $abono->usuario?->name }}</td>
                                        <td class="py-2 text-right">{{ number_format((float) $abono->monto, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if (auth()->user()->esAdministrador() && (float) $venta->credito_saldo_pendiente > 0 && $venta->estado === \App\Enums\EstadoVenta::Confirmada)
                        <form method="POST" action="{{ route('admin.creditos.abonos.store', $venta) }}" class="mt-4 flex flex-wrap items-end gap-3">
                            @csrf
                            <div>
                                <x-input-label for="monto" :value="__('Monto del abono')" />
                                <x-text-input id="monto" name="monto" type="number" step="0.01" min="0.01"
                                    :max="(float) $venta->credito_saldo_pendiente" class="mt-1 block w-40" required />
                                <x-input-error :messages="$errors->get('monto')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="fecha" :value="__('Fecha')" />
                                <x-text-input id="fecha" name="fecha" type="date" :value="old('fecha', now()->toDateString())"
                                    max="{{ now()->toDateString() }}" class="mt-1 block" required />
                                <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                            </div>
                            <x-primary-button>{{ __('Registrar abono') }}</x-primary-button>
                        </form>
                    @endif
                </div>
            @endif

            @can('entregar', $venta)
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <form method="POST" action="{{ route('ventas.entregar', $venta) }}"
                          onsubmit="return confirm('{{ __('¿Marcar esta venta como entregada? Después ya no se podrá anular.') }}')">
                        @csrf @method('PATCH')
                        <x-primary-button>{{ __('Marcar como entregada') }}</x-primary-button>
                    </form>
                </div>
            @endcan

            @can('anular', $venta)
                <div class="bg-white shadow sm:rounded-lg p-6 border border-red-100">
                    <h3 class="font-semibold text-gray-800">{{ __('Anular venta') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Reintegra el stock automáticamente. Solo posible antes de la entrega.') }}</p>
                    <form method="POST" action="{{ route('ventas.anular', $venta) }}" class="mt-3 space-y-3"
                          onsubmit="return confirm('{{ __('¿Anular la venta') }} {{ $venta->numero }}?')">
                        @csrf @method('PATCH')
                        <div>
                            <x-input-label for="motivo" :value="__('Motivo')" />
                            <textarea id="motivo" name="motivo" rows="2" required
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('motivo') }}</textarea>
                            <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                        </div>
                        <x-danger-button>{{ __('Anular venta') }}</x-danger-button>
                    </form>
                </div>
            @endcan

            @if (auth()->user()->esAdministrador() && $venta->entregada_at && $venta->estado === \App\Enums\EstadoVenta::Confirmada)
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ __('Devoluciones') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Tras la entrega, el camino es la devolución (genera saldo a favor, RN-11).') }}</p>
                        </div>
                        <a href="{{ route('admin.devoluciones.create', $venta) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            {{ __('Registrar devolución') }}
                        </a>
                    </div>

                    @if ($venta->devoluciones->isNotEmpty())
                        <ul class="mt-4 space-y-1 text-sm">
                            @foreach ($venta->devoluciones as $devolucion)
                                <li>
                                    {{ $devolucion->fecha->format('Y-m-d') }} ·
                                    {{ $devolucion->estado->label() }} ·
                                    {{ __('unidades:') }} {{ $devolucion->lineas->sum('cantidad') }} ·
                                    {{ __('saldo generado:') }} {{ number_format((float) $devolucion->saldo_generado, 2) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
