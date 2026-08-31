@php
    $inputSm = 'block w-full rounded-lg border-line bg-surface text-sm text-ink shadow-xs focus:border-primary-500 focus:ring-2 focus:ring-primary-200';
    $check = 'rounded border-line text-primary-600 focus:ring-2 focus:ring-primary-200';
@endphp

<x-app-layout>
    <x-page :subtitle="$venta->numero">
        <x-slot name="heading">
            <h1 class="text-2xl font-semibold tracking-tight text-ink">
                {{ __('Devolución de la venta') }}
            </h1>
        </x-slot>

        <x-card class="max-w-3xl">
            <x-input-error :messages="$errors->get('lineas')" class="mb-5" />

            <p class="mb-5 text-sm text-ink-soft">
                {{ __('Cliente:') }} {{ $venta->cliente?->nombre ?? '—' }}.
                {{ __('Una devolución validada genera saldo a favor (nunca efectivo). Marca por línea si la unidad vuelve al inventario.') }}
            </p>

            <form method="POST" action="{{ route('admin.devoluciones.store', $venta) }}" class="space-y-5">
                @csrf

                <div class="divide-y divide-line border-y border-line">
                    @foreach ($venta->lineas as $linea)
                        @php($devuelta = $linea->cantidadDevuelta())
                        @php($disponible = $linea->cantidad - $devuelta)
                        <div class="grid grid-cols-12 items-center gap-3 py-3">
                            <label class="col-span-12 flex items-start gap-2 text-sm sm:col-span-6">
                                <input type="checkbox" name="lineas[{{ $linea->id }}][incluir]" value="1"
                                    @disabled($disponible < 1) class="mt-0.5 {{ $check }}">
                                <span>
                                    <span class="text-ink">{{ $linea->variante->producto->nombre }}</span>
                                    <span class="text-ink-faint">— {{ $linea->variante->etiqueta() }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-faint">
                                        {{ __('Vendidas:') }} {{ $linea->cantidad }} ·
                                        {{ __('ya devueltas:') }} {{ $devuelta }} ·
                                        {{ __('pagó/unidad:') }} {{ number_format((float) $linea->valorUnitarioPagado(), 2) }}
                                    </span>
                                </span>
                            </label>
                            <div class="col-span-5 sm:col-span-3">
                                <input type="number" min="1" max="{{ $disponible }}" step="1"
                                    name="lineas[{{ $linea->id }}][cantidad]" placeholder="{{ __('Cantidad') }}"
                                    @disabled($disponible < 1) class="{{ $inputSm }}">
                            </div>
                            <label class="col-span-7 flex items-center gap-2 text-xs text-ink-soft sm:col-span-3">
                                <input type="checkbox" name="lineas[{{ $linea->id }}][reintegra_inventario]" value="1" checked
                                    @disabled($disponible < 1) class="{{ $check }}">
                                {{ __('Vuelve al stock') }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="estado" :value="__('Resultado')" />
                        <select id="estado" name="estado" required class="mt-1.5 {{ $inputSm }}">
                            <option value="validada">{{ __('Validada (genera saldo a favor)') }}</option>
                            <option value="rechazada">{{ __('Rechazada (sin efecto)') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('estado')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="fecha" :value="__('Fecha')" />
                        <x-text-input id="fecha" name="fecha" type="date" :value="old('fecha', now()->toDateString())"
                            max="{{ now()->toDateString() }}" class="mt-1.5" required />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="motivo" :value="__('Motivo')" />
                    <textarea id="motivo" name="motivo" rows="2" required class="mt-1.5 {{ $inputSm }}">{{ old('motivo') }}</textarea>
                    <x-input-error :messages="$errors->get('motivo')" class="mt-1.5" />
                </div>

                <div class="flex items-center gap-3">
                    <x-button>{{ __('Registrar devolución') }}</x-button>
                    <x-button variant="ghost" :href="route('ventas.show', $venta)">{{ __('Cancelar') }}</x-button>
                </div>
            </form>
        </x-card>
    </x-page>
</x-app-layout>
