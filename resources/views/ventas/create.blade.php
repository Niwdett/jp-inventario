<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Registrar venta') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">

                @if (session('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">{{ session('error') }}</div>
                @endif

                <x-input-error :messages="$errors->get('lineas')" class="mb-4" />

                <form method="POST" action="{{ route('ventas.store') }}"
                      x-data="{
                          esAdmin: {{ auth()->user()->esAdministrador() ? 'true' : 'false' }},
                          variantes: {{ Js::from($variantes) }},
                          clientes: {{ Js::from($clientes) }},
                          clientesEnMora: {{ Js::from($clientesEnMora) }},
                          cliente_id: '{{ old('cliente_id') }}',
                          metodo_pago: '{{ old('metodo_pago', 'efectivo') }}',
                          saldo_favor_aplicado: '{{ old('saldo_favor_aplicado') }}',
                          lineas: [{ variante_id: '', cantidad: 1, precio_unitario: '', descuento_porcentaje: '' }],
                          agregar() { this.lineas.push({ variante_id: '', cantidad: 1, precio_unitario: '', descuento_porcentaje: '' }) },
                          quitar(i) { if (this.lineas.length > 1) this.lineas.splice(i, 1) },
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

                    <div class="space-y-4">
                        <template x-for="(linea, i) in lineas" :key="i">
                            <div class="grid grid-cols-12 gap-2 items-end border-b border-gray-100 pb-3">
                                <div class="col-span-12 sm:col-span-5">
                                    <x-input-label ::for="'variante_' + i" :value="__('Variante')" />
                                    <select ::id="'variante_' + i" :name="'lineas[' + i + '][variante_id]'" x-model="linea.variante_id" required
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">{{ __('— Selecciona —') }}</option>
                                        <template x-for="(label, id) in variantes" :key="id">
                                            <option :value="id" x-text="label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <x-input-label ::for="'cantidad_' + i" :value="__('Cantidad')" />
                                    <input type="number" min="1" step="1" x-model="linea.cantidad" :name="'lineas[' + i + '][cantidad]'" required
                                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <x-input-label ::for="'precio_' + i" :value="__('Precio')" />
                                    <input type="number" min="0" step="0.01" x-model="linea.precio_unitario" :name="'lineas[' + i + '][precio_unitario]'" required
                                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div class="col-span-3 sm:col-span-2">
                                    <x-input-label ::for="'desc_' + i" :value="__('Desc. %')" />
                                    <input type="number" min="0" max="100" step="0.01" x-model="linea.descuento_porcentaje" :name="'lineas[' + i + '][descuento_porcentaje]'"
                                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <div class="col-span-1 flex justify-end">
                                    <button type="button" @click="quitar(i)" x-show="lineas.length > 1"
                                            class="text-red-600 hover:text-red-900 text-lg leading-none pb-2">&times;</button>
                                </div>
                                <div class="col-span-12 text-right text-xs text-gray-500">
                                    {{ __('Importe:') }} <span x-text="importe(linea)"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="agregar" class="mt-3 text-sm text-indigo-600 hover:text-indigo-900">
                        + {{ __('Agregar línea') }}
                    </button>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="cliente_id" :value="__('Cliente')" />
                            <select id="cliente_id" name="cliente_id" x-model="cliente_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('— Sin cliente (contado) —') }}</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->nombre }}@if ($cliente->cedula) ({{ $cliente->cedula }})@endif</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-red-600" x-show="clienteRequerido && !cliente_id" x-cloak>
                                {{ __('El crédito o el uso de saldo a favor requieren un cliente.') }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500" x-show="clienteSel" x-cloak>
                                {{ __('Saldo a favor disponible:') }} <span class="font-semibold" x-text="saldoDisponible.toFixed(2)"></span>
                            </p>
                            <x-input-error :messages="$errors->get('cliente_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="metodo_pago" :value="__('Método de pago del restante')" />
                            <select id="metodo_pago" name="metodo_pago" x-model="metodo_pago" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($metodosPago as $metodo)
                                    <option value="{{ $metodo->value }}">{{ $metodo->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('metodo_pago')" class="mt-2" />
                        </div>

                        <div x-show="clienteSel" x-cloak>
                            <x-input-label for="saldo_favor_aplicado" :value="__('Saldo a favor a aplicar')" />
                            <input type="number" min="0" step="0.01" id="saldo_favor_aplicado" name="saldo_favor_aplicado" x-model="saldo_favor_aplicado"
                                   class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <x-input-error :messages="$errors->get('saldo_favor_aplicado')" class="mt-2" />
                        </div>

                        <div class="flex items-end justify-end text-sm text-gray-600">
                            <div class="text-right space-y-0.5">
                                <p>{{ __('Total:') }} <span class="font-semibold text-gray-800" x-text="total.toFixed(2)"></span></p>
                                <p x-show="saldoAplicado > 0" x-cloak>{{ __('Saldo a favor:') }} <span x-text="'-' + saldoAplicado.toFixed(2)"></span></p>
                                <p class="text-lg font-semibold text-gray-800">{{ __('A pagar:') }} <span x-text="restante.toFixed(2)"></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800"
                         x-show="esCredito && clienteEnMora" x-cloak>
                        <p class="font-semibold">{{ __('Este cliente está en mora (RN-09).') }}</p>
                        <template x-if="esAdmin">
                            <label class="mt-2 flex items-center gap-2">
                                <input type="checkbox" name="autorizar_mora" value="1"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>{{ __('Autorizo esta venta a crédito pese a la mora.') }}</span>
                            </label>
                        </template>
                        <p class="mt-1" x-show="!esAdmin">{{ __('Solo un Administrador puede autorizar la venta a crédito.') }}</p>
                    </div>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>{{ __('Confirmar venta') }}</x-primary-button>
                        <a href="{{ route('ventas.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('Cancelar') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
