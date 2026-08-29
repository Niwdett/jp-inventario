<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Devolución de la venta') }} <span class="font-mono text-gray-500">{{ $venta->numero }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">

                @if (session('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">{{ session('error') }}</div>
                @endif
                <x-input-error :messages="$errors->get('lineas')" class="mb-4" />

                <p class="text-sm text-gray-500 mb-4">
                    {{ __('Cliente:') }} {{ $venta->cliente?->nombre ?? '—' }}.
                    {{ __('Una devolución validada genera saldo a favor (nunca efectivo). Marca por línea si la unidad vuelve al inventario.') }}
                </p>

                <form method="POST" action="{{ route('admin.devoluciones.store', $venta) }}" class="space-y-4">
                    @csrf

                    <div class="divide-y divide-gray-100 border-y border-gray-100">
                        @foreach ($venta->lineas as $linea)
                            @php($devuelta = $linea->cantidadDevuelta())
                            @php($disponible = $linea->cantidad - $devuelta)
                            <div class="py-3 grid grid-cols-12 gap-2 items-center">
                                <label class="col-span-6 flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="lineas[{{ $linea->id }}][incluir]" value="1"
                                        @disabled($disponible < 1)
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>
                                        {{ $linea->variante->producto->nombre }}
                                        <span class="text-gray-400">— {{ $linea->variante->etiqueta() }}</span>
                                        <span class="block text-xs text-gray-400">
                                            {{ __('Vendidas:') }} {{ $linea->cantidad }} ·
                                            {{ __('ya devueltas:') }} {{ $devuelta }} ·
                                            {{ __('pagó/unidad:') }} {{ number_format((float) $linea->valorUnitarioPagado(), 2) }}
                                        </span>
                                    </span>
                                </label>
                                <div class="col-span-3">
                                    <input type="number" min="1" max="{{ $disponible }}" step="1"
                                        name="lineas[{{ $linea->id }}][cantidad]" placeholder="{{ __('Cantidad') }}"
                                        @disabled($disponible < 1)
                                        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <label class="col-span-3 flex items-center gap-2 text-xs text-gray-600">
                                    <input type="checkbox" name="lineas[{{ $linea->id }}][reintegra_inventario]" value="1" checked
                                        @disabled($disponible < 1)
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ __('Vuelve al stock') }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="estado" :value="__('Resultado')" />
                            <select id="estado" name="estado" required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="validada">{{ __('Validada (genera saldo a favor)') }}</option>
                                <option value="rechazada">{{ __('Rechazada (sin efecto)') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="fecha" :value="__('Fecha')" />
                            <x-text-input id="fecha" name="fecha" type="date" :value="old('fecha', now()->toDateString())"
                                max="{{ now()->toDateString() }}" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="motivo" :value="__('Motivo')" />
                        <textarea id="motivo" name="motivo" rows="2" required
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('motivo') }}</textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Registrar devolución') }}</x-primary-button>
                        <a href="{{ route('ventas.show', $venta) }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('Cancelar') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
