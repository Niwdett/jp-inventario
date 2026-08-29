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
                          variantes: {{ Js::from($variantes) }},
                          lineas: [{ variante_id: '', cantidad: 1, precio_unitario: '', descuento_porcentaje: '' }],
                          agregar() { this.lineas.push({ variante_id: '', cantidad: 1, precio_unitario: '', descuento_porcentaje: '' }) },
                          quitar(i) { if (this.lineas.length > 1) this.lineas.splice(i, 1) },
                          importe(l) {
                              const bruto = (parseFloat(l.precio_unitario) || 0) * (parseInt(l.cantidad) || 0);
                              const desc = parseFloat(l.descuento_porcentaje) || 0;
                              return (bruto * (1 - desc / 100)).toFixed(2);
                          },
                          get total() { return this.lineas.reduce((s, l) => s + parseFloat(this.importe(l)), 0).toFixed(2); }
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
                            <x-input-label for="metodo_pago" :value="__('Método de pago')" />
                            <select id="metodo_pago" name="metodo_pago" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($metodosPago as $metodo)
                                    <option value="{{ $metodo->value }}" @selected(old('metodo_pago') === $metodo->value)>{{ $metodo->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('metodo_pago')" class="mt-2" />
                        </div>
                        <div class="flex items-end justify-end">
                            <p class="text-lg font-semibold text-gray-800">
                                {{ __('Total:') }} <span x-text="total"></span>
                            </p>
                        </div>
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
