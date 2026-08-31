<x-app-layout>
    <x-page>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-ink">
                    {{ __('Venta') }} <span class="font-mono text-lg font-normal text-ink-faint">{{ $venta->numero }}</span>
                </h1>
                @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                    <x-badge variant="danger">{{ __('Anulada') }}</x-badge>
                @elseif ($venta->entregada_at)
                    <x-badge variant="success">{{ __('Entregada') }}</x-badge>
                @else
                    <x-badge variant="info">{{ __('Confirmada') }}</x-badge>
                @endif
            </div>
        </x-slot>
        <x-slot name="actions">
            <x-button variant="secondary" :href="route('ventas.index')" class="print:hidden">
                <x-icon name="arrow-left" class="size-4" />
                {{ __('Volver') }}
            </x-button>
            <x-print-button />
        </x-slot>

        <div class="hidden print:block">
            <p class="font-display text-xl font-bold text-ink">{{ config('app.name', 'JP') }} · {{ __('Ropa & Calzado') }}</p>
            <p class="text-sm text-ink-soft">{{ __('Comprobante de venta') }} · {{ $venta->numero }} · {{ $venta->fecha_venta->format('Y-m-d H:i') }}</p>
        </div>

        <x-card>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Fecha') }}</dt><dd class="mt-0.5 text-ink">{{ $venta->fecha_venta->format('Y-m-d H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Vendedor') }}</dt><dd class="mt-0.5 text-ink">{{ $venta->usuario->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Cliente') }}</dt>
                    <dd class="mt-0.5 text-ink">
                        @if ($venta->cliente)
                            <a href="{{ route('admin.clientes.show', $venta->cliente) }}" class="font-medium text-primary-700 hover:text-primary-800">{{ $venta->cliente->nombre }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Método de pago') }}</dt><dd class="mt-0.5 text-ink">{{ $venta->metodo_pago->label() }}</dd>
                </div>
                @if ((float) $venta->saldo_favor_aplicado > 0)
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Saldo a favor aplicado') }}</dt>
                        <dd class="mt-0.5 tabular-nums text-ink"><x-money :value="$venta->saldo_favor_aplicado" /></dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 sm:block">
                    <dt class="text-ink-faint">{{ __('Entrega') }}</dt>
                    <dd class="mt-0.5 text-ink">{{ $venta->entregada_at?->format('Y-m-d H:i') ?? __('Pendiente') }}</dd>
                </div>
                @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Anulada por') }}</dt>
                        <dd class="mt-0.5 text-ink">{{ $venta->anuladaPor?->name }} · {{ $venta->anulada_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Motivo') }}</dt><dd class="mt-0.5 text-ink">{{ $venta->motivo_anulacion }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Producto') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Cantidad') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Precio') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Desc. %') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Importe') }}</th>
                </x-slot>

                @foreach ($venta->lineas as $linea)
                    <tr>
                        <td class="px-5 py-3">
                            <span class="text-ink">{{ $linea->variante->producto->nombre }}</span>
                            <span class="text-ink-faint">— {{ $linea->variante->etiqueta() }}</span>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $linea->cantidad }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft"><x-money :value="$linea->precio_unitario" /></td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ $linea->descuento_porcentaje ? number_format((float) $linea->descuento_porcentaje, 2) : '—' }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink"><x-money :value="$linea->importe_linea" /></td>
                    </tr>
                @endforeach

                <x-slot name="foot">
                    <tr>
                        <td class="px-5 py-2 text-right text-ink-soft" colspan="4">{{ __('Subtotal') }}</td>
                        <td class="px-5 py-2 text-right tabular-nums"><x-money :value="$venta->subtotal" /></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-2 text-right text-ink-soft" colspan="4">{{ __('Descuento') }}</td>
                        <td class="px-5 py-2 text-right tabular-nums"><x-money :value="$venta->descuento_total" /></td>
                    </tr>
                    <tr class="font-semibold text-ink">
                        <td class="px-5 py-2.5 text-right" colspan="4">{{ __('Total') }}</td>
                        <td class="px-5 py-2.5 text-right tabular-nums"><x-money :value="$venta->total" /></td>
                    </tr>
                </x-slot>
            </x-table>
        </x-card>

        @if ($venta->esCredito())
            <x-card :title="__('Crédito')">
                <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Deuda inicial') }}</dt>
                        <dd class="mt-0.5 tabular-nums text-ink"><x-money :value="$venta->credito_monto" /></dd>
                    </div>
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Saldo pendiente') }}</dt>
                        <dd class="mt-0.5 font-semibold tabular-nums {{ (float) $venta->credito_saldo_pendiente > 0 ? 'text-warning-700' : 'text-success-700' }}">
                            <x-money :value="$venta->credito_saldo_pendiente" />
                            @if ((float) $venta->credito_saldo_pendiente <= 0) · {{ __('saldada') }} @endif
                        </dd>
                    </div>
                    @if ($venta->credito_autorizado_por)
                        <div class="flex justify-between gap-4 sm:block">
                            <dt class="text-ink-faint">{{ __('Autorizada en mora por') }}</dt>
                            <dd class="mt-0.5 text-ink">{{ $venta->creditoAutorizadoPor?->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($venta->abonos->isNotEmpty())
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-line text-left text-xs font-medium uppercase tracking-wide text-ink-faint">
                                <tr>
                                    <th class="py-2 pr-4">{{ __('Fecha') }}</th>
                                    <th class="py-2 pr-4">{{ __('Registró') }}</th>
                                    <th class="py-2 text-right">{{ __('Monto') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($venta->abonos as $abono)
                                    <tr>
                                        <td class="py-2 pr-4 text-ink-soft">{{ $abono->fecha->format('Y-m-d') }}</td>
                                        <td class="py-2 pr-4 text-ink-soft">{{ $abono->usuario?->name }}</td>
                                        <td class="py-2 text-right tabular-nums text-ink"><x-money :value="$abono->monto" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('abonar', $venta)
                    <form method="POST" action="{{ route('admin.creditos.abonos.store', $venta) }}" class="mt-4 flex flex-wrap items-end gap-3 border-t border-line pt-4 print:hidden">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <div>
                            <x-input-label for="monto" :value="__('Monto del abono')" />
                            <x-text-input id="monto" name="monto" type="number" step="0.01" min="0.01"
                                :max="(float) $venta->credito_saldo_pendiente" class="mt-1.5 w-40" required />
                            <x-input-error :messages="$errors->get('monto')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="fecha" :value="__('Fecha')" />
                            <x-text-input id="fecha" name="fecha" type="date" :value="old('fecha', now()->toDateString())"
                                max="{{ now()->toDateString() }}" class="mt-1.5" required />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                        </div>
                        <x-button>{{ __('Registrar abono') }}</x-button>
                    </form>
                @endcan
            </x-card>
        @endif

        @can('entregar', $venta)
            <x-card class="print:hidden">
                <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-ink">{{ __('Entrega') }}</p>
                        <p class="mt-0.5 text-sm text-ink-soft">{{ __('Una vez entregada, la venta ya no se podrá anular.') }}</p>
                    </div>
                    <form method="POST" action="{{ route('ventas.entregar', $venta) }}"
                          data-confirm="{{ __('Después de entregarla, la venta ya no se podrá anular (solo devolución).') }}"
                          data-confirm-title="{{ __('Marcar como entregada') }}"
                          data-confirm-label="{{ __('Marcar entregada') }}"
                          data-confirm-variant="primary">
                        @csrf @method('PATCH')
                        <x-button>
                            <x-icon name="entrega" class="size-4" />
                            {{ __('Marcar como entregada') }}
                        </x-button>
                    </form>
                </div>
            </x-card>
        @endcan

        @can('anular', $venta)
            <x-card class="border-danger-200 print:hidden">
                <h3 class="font-semibold text-ink">{{ __('Anular venta') }}</h3>
                <p class="mt-1 text-sm text-ink-soft">{{ __('Reintegra el stock automáticamente. Solo posible antes de la entrega.') }}</p>
                <form method="POST" action="{{ route('ventas.anular', $venta) }}" class="mt-3 space-y-3"
                      data-confirm="{{ __('Se anulará la venta :numero y se reintegrará el stock. No se puede deshacer.', ['numero' => $venta->numero]) }}"
                      data-confirm-title="{{ __('Anular venta') }}"
                      data-confirm-label="{{ __('Anular venta') }}">
                    @csrf @method('PATCH')
                    <div>
                        <x-input-label for="motivo" :value="__('Motivo')" />
                        <textarea id="motivo" name="motivo" rows="2" required
                                  class="mt-1.5 block w-full rounded-lg border-line bg-surface text-sm text-ink shadow-xs focus:border-danger-400 focus:ring-2 focus:ring-danger-200">{{ old('motivo') }}</textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-1.5" />
                    </div>
                    <x-button variant="danger">{{ __('Anular venta') }}</x-button>
                </form>
            </x-card>
        @endcan

        @if (auth()->user()->esAdministrador() && $venta->entregada_at && $venta->estado === \App\Enums\EstadoVenta::Confirmada)
            <x-card class="print:hidden">
                <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-ink">{{ __('Devoluciones') }}</h3>
                        <p class="mt-1 text-sm text-ink-soft">{{ __('Tras la entrega, el camino es la devolución (genera saldo a favor, RN-11).') }}</p>
                    </div>
                    <x-button :href="route('admin.devoluciones.create', $venta)">
                        <x-icon name="devoluciones" class="size-4" />
                        {{ __('Registrar devolución') }}
                    </x-button>
                </div>

                @if ($venta->devoluciones->isNotEmpty())
                    <ul class="mt-4 space-y-1.5 border-t border-line pt-4 text-sm text-ink-soft">
                        @foreach ($venta->devoluciones as $devolucion)
                            <li>
                                {{ $devolucion->fecha->format('Y-m-d') }} ·
                                {{ $devolucion->estado->label() }} ·
                                {{ __('unidades:') }} {{ $devolucion->lineas->sum('cantidad') }} ·
                                {{ __('saldo generado:') }} <x-money :value="$devolucion->saldo_generado" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endif

    </x-page>
</x-app-layout>
