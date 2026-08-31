@php
    $inputSm = 'mt-1 block w-full rounded-lg border-line bg-surface text-sm text-ink shadow-xs focus:border-primary-500 focus:ring-2 focus:ring-primary-200';

    // Restaura las líneas tras un error de validación; si no, una línea vacía.
    $lineasIniciales = collect(old('lineas', [[]]))
        ->map(fn ($l) => is_array($l) ? [
            'variante_id' => $l['variante_id'] ?? '',
            'cantidad' => $l['cantidad'] ?? 1,
            'precio_unitario' => $l['precio_unitario'] ?? '',
            'descuento_porcentaje' => $l['descuento_porcentaje'] ?? '',
        ] : null)
        ->filter()
        ->values()
        ->whenEmpty(fn () => collect([['variante_id' => '', 'cantidad' => 1, 'precio_unitario' => '', 'descuento_porcentaje' => '']]));
@endphp

<x-app-layout>
    <x-page :title="__('Registrar venta')">
        <x-card class="max-w-3xl">

            <x-input-error :messages="$errors->get('lineas')" class="mb-5" />

            <form method="POST" action="{{ route('ventas.store') }}"
                  x-data="{
                      esAdmin: {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
                      variantes: {{ Js::from($variantes) }},
                      preciosReferencia: {{ Js::from($preciosReferencia) }},
                      clientes: {{ Js::from($clientes) }},
                      clientesEnMora: {{ Js::from($clientesEnMora) }},
                      cliente_id: '{{ old('cliente_id') }}',
                      metodo_pago: '{{ old('metodo_pago', 'efectivo') }}',
                      saldo_favor_aplicado: '{{ old('saldo_favor_aplicado') }}',
                      lineas: {{ Js::from($lineasIniciales) }},
                      agregar() { this.lineas.push({ variante_id: '', cantidad: 1, precio_unitario: '', descuento_porcentaje: '' }) },
                      quitar(i) { if (this.lineas.length > 1) this.lineas.splice(i, 1) },
                      sugerirPrecio(l) {
                          const sugerido = this.preciosReferencia[l.variante_id];
                          if (sugerido !== undefined && (l.precio_unitario === '' || l.precio_unitario === null)) {
                              l.precio_unitario = sugerido;
                          }
                      },
                      formatoMoneda(v) { return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                      importe(l) {
                          const bruto = (parseFloat(l.precio_unitario) || 0) * (parseInt(l.cantidad) || 0);
                          const desc = parseFloat(l.descuento_porcentaje) || 0;
                          return (bruto * (1 - desc / 100)).toFixed(2);
                      },
                      get total() { return this.lineas.reduce((s, l) => s + parseFloat(this.importe(l)), 0); },
                      get clienteSel() { return this.clientes.find(c => String(c.id) === String(this.cliente_id)) || null; },
                      get saldoDisponible() { return this.clienteSel ? parseFloat(this.clienteSel.saldo_favor) : 0; },
                      get saldoAplicado() { return Math.min(parseFloat(this.saldo_favor_aplicado) || 0, this.total); },
                      get restante() { return Math.max(this.total - this.saldoAplicado, 0); },
                      get esCredito() { return this.metodo_pago === 'credito' && this.restante > 0; },
                      get clienteRequerido() { return this.metodo_pago === 'credito' || this.saldoAplicado > 0; },
                      get clienteEnMora() { return this.cliente_id && this.clientesEnMora.map(String).includes(String(this.cliente_id)); },
                  }">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">

                <div class="space-y-3">
                    <template x-for="(linea, i) in lineas" :key="i">
                        <div class="grid grid-cols-12 items-end gap-2 border-b border-line pb-3">
                            <div class="col-span-12 sm:col-span-5">
                                <x-input-label ::for="'variante_' + i" :value="__('Variante')" />
                                <select ::id="'variante_' + i" :name="'lineas[' + i + '][variante_id]'" x-model="linea.variante_id"
                                        x-on:change="sugerirPrecio(linea)" required
                                        class="{{ $inputSm }}">
                                    <option value="">{{ __('— Selecciona —') }}</option>
                                    <template x-for="(label, id) in variantes" :key="id">
                                        <option :value="id" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <x-input-label ::for="'cantidad_' + i" :value="__('Cantidad')" />
                                <input type="number" min="1" step="1" x-model="linea.cantidad" :name="'lineas[' + i + '][cantidad]'" required
                                       class="{{ $inputSm }}">
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <x-input-label ::for="'precio_' + i">
                                    <span class="inline-flex items-center gap-1">
                                        {{ __('Precio') }}
                                        <x-info-hint :title="__('Precio de referencia')" align="right">
                                            <template x-if="preciosReferencia[linea.variante_id] !== undefined">
                                                <span class="mb-1 block font-medium text-ink" x-text="'$ ' + formatoMoneda(preciosReferencia[linea.variante_id])"></span>
                                            </template>
                                            {{ __('Precio sugerido para este producto. Puedes modificarlo para aplicar descuentos u otros ajustes.') }}
                                        </x-info-hint>
                                    </span>
                                </x-input-label>
                                <input type="number" min="0" step="0.01" x-model="linea.precio_unitario" :name="'lineas[' + i + '][precio_unitario]'" required
                                       class="{{ $inputSm }}">
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <x-input-label ::for="'desc_' + i" :value="__('Desc. %')" />
                                <input type="number" min="0" max="100" step="0.01" x-model="linea.descuento_porcentaje" :name="'lineas[' + i + '][descuento_porcentaje]'"
                                       class="{{ $inputSm }}">
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button type="button" @click="quitar(i)" x-show="lineas.length > 1" x-cloak
                                        class="pb-2 text-lg leading-none text-danger-600 transition-colors hover:text-danger-700">&times;</button>
                            </div>
                            <div class="col-span-12 text-right text-xs text-ink-faint">
                                {{ __('Importe:') }} <span class="tabular-nums" x-text="importe(linea)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="agregar"
                        class="mt-3 text-sm font-medium text-primary-700 transition-colors hover:text-primary-800">
                    + {{ __('Agregar línea') }}
                </button>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="cliente_id" :value="__('Cliente')" />
                            @can('create', \App\Models\Cliente::class)
                                <a href="{{ route('admin.clientes.create') }}" target="_blank"
                                   class="text-xs font-medium text-primary-700 hover:text-primary-800">+ {{ __('Registrar cliente') }}</a>
                            @endcan
                        </div>
                        <select id="cliente_id" name="cliente_id" x-model="cliente_id" class="{{ $inputSm }}">
                            <option value="">{{ __('— Sin cliente (contado) —') }}</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->nombre }}@if ($cliente->cedula) ({{ $cliente->cedula }})@endif</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-danger-600" x-show="clienteRequerido && !cliente_id" x-cloak>
                            {{ __('El crédito o el uso de saldo a favor requieren un cliente.') }}
                        </p>
                        <p class="mt-1 text-xs text-ink-faint" x-show="clienteSel" x-cloak>
                            {{ __('Saldo a favor disponible:') }} <span class="font-semibold text-ink-soft" x-text="saldoDisponible.toFixed(2)"></span>
                        </p>
                        <x-input-error :messages="$errors->get('cliente_id')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="metodo_pago" :value="__('Método de pago del restante')" />
                        <select id="metodo_pago" name="metodo_pago" x-model="metodo_pago" required class="{{ $inputSm }}">
                            @foreach ($metodosPago as $metodo)
                                <option value="{{ $metodo->value }}">{{ $metodo->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('metodo_pago')" class="mt-1.5" />
                    </div>

                    <div x-show="clienteSel" x-cloak>
                        <x-input-label for="saldo_favor_aplicado" :value="__('Saldo a favor a aplicar')" />
                        <input type="number" min="0" step="0.01" id="saldo_favor_aplicado" name="saldo_favor_aplicado" x-model="saldo_favor_aplicado"
                               class="{{ $inputSm }}">
                        <x-input-error :messages="$errors->get('saldo_favor_aplicado')" class="mt-1.5" />
                    </div>

                    <div class="flex items-end justify-end">
                        <div class="space-y-1 rounded-lg bg-surface-sunken px-4 py-3 text-right text-sm text-ink-soft">
                            <p>{{ __('Total:') }} <span class="font-semibold text-ink tabular-nums" x-text="total.toFixed(2)"></span></p>
                            <p x-show="saldoAplicado > 0" x-cloak>{{ __('Saldo a favor:') }} <span class="tabular-nums" x-text="'-' + saldoAplicado.toFixed(2)"></span></p>
                            <p class="text-base font-semibold text-ink">{{ __('A pagar:') }} <span class="tabular-nums" x-text="restante.toFixed(2)"></span></p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-warning-200 bg-warning-50 p-3 text-sm text-warning-700"
                     x-show="esCredito && clienteEnMora" x-cloak>
                    <p class="font-semibold">{{ __('Este cliente está en mora (RN-09).') }}</p>
                    <template x-if="esAdmin">
                        <label class="mt-2 flex items-center gap-2">
                            <input type="checkbox" name="autorizar_mora" value="1"
                                   class="rounded border-warning-300 text-primary-600 focus:ring-2 focus:ring-primary-200">
                            <span>{{ __('Autorizo esta venta a crédito pese a la mora.') }}</span>
                        </label>
                    </template>
                    <p class="mt-1" x-show="!esAdmin">{{ __('Solo un Administrador puede autorizar la venta a crédito.') }}</p>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <x-button>{{ __('Confirmar venta') }}</x-button>
                    <x-button variant="ghost" :href="route('ventas.index')">{{ __('Cancelar') }}</x-button>
                </div>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
