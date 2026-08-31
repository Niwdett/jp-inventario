<x-app-layout>
    <x-page>
        <x-slot name="heading">
            <h1 class="text-2xl font-semibold tracking-tight text-ink">
                {{ $producto->nombre }}
                <span class="ml-2 font-mono text-base font-normal text-ink-faint">{{ $producto->codigo_interno }}</span>
            </h1>
        </x-slot>
        <x-slot name="actions">
            <x-button variant="secondary" :href="route('admin.productos.historial', $producto)">
                <x-icon name="historial" class="size-4" />
                {{ __('Historial') }}
            </x-button>
            <x-button :href="route('admin.productos.edit', $producto)">
                <x-icon name="editar" class="size-4" />
                {{ __('Editar producto') }}
            </x-button>
        </x-slot>

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card>
            <div class="flex flex-col gap-6 sm:flex-row">
                @if ($producto->foto)
                    <img src="{{ Storage::url($producto->foto) }}" alt=""
                         class="h-32 w-32 shrink-0 rounded-lg border border-line object-cover">
                @endif
                <dl class="grid flex-1 grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Categoría') }}</dt><dd class="mt-0.5 text-ink">{{ $producto->categoria->nombre }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Marca') }}</dt><dd class="mt-0.5 text-ink">{{ $producto->marca ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Precio de referencia') }}</dt><dd class="mt-0.5 tabular-nums text-ink">{{ number_format((float) $producto->precio_referencia, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Umbral de stock bajo') }}</dt><dd class="mt-0.5 tabular-nums text-ink">{{ $producto->umbral_stock_bajo }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 sm:block">
                        <dt class="text-ink-faint">{{ __('Proveedor') }}</dt><dd class="mt-0.5 text-ink">{{ $producto->proveedor ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </x-card>

        <x-card :title="__('Variantes')" flush>
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Talla') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Color') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Código') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Stock') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Costo promedio') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                </x-slot>

                @foreach ($producto->variantes as $variante)
                    <tr class="transition-colors hover:bg-surface-sunken/60">
                        <td class="px-5 py-3 text-ink">{{ $variante->talla }}</td>
                        <td class="px-5 py-3 text-ink">{{ $variante->color }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-ink-soft">{{ $variante->codigo ?: '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <span class="tabular-nums">{{ $variante->stock }}</span>
                            @if ($variante->estaEnStockBajo())
                                <x-badge variant="warning" class="ml-1">{{ __('Stock bajo') }}</x-badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $variante->costo_promedio, 4) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <x-icon-button icon="entrada" :label="__('Registrar entrada')"
                                    :href="route('admin.inventario.entradas.create', ['variante_id' => $variante->id])" />
                                <x-icon-button icon="ajuste" :label="__('Registrar ajuste')"
                                    :href="route('admin.inventario.ajustes.create', ['variante_id' => $variante->id])" />
                                <x-icon-button icon="editar" :label="__('Editar variante')"
                                    :href="route('admin.productos.variantes.edit', [$producto, $variante])" />
                                @if ($producto->variantes->count() > 1)
                                    <form method="POST" action="{{ route('admin.productos.variantes.destroy', [$producto, $variante]) }}"
                                          data-confirm="{{ __('Se eliminará la variante «:etiqueta».', ['etiqueta' => $variante->etiqueta()]) }}"
                                          data-confirm-title="{{ __('Eliminar variante') }}"
                                          data-confirm-label="{{ __('Eliminar') }}">
                                        @csrf @method('DELETE')
                                        <x-icon-button icon="eliminar" :label="__('Eliminar variante')" variant="danger" />
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="border-t border-line bg-surface-sunken/50 px-5 py-4">
                <form method="POST" action="{{ route('admin.productos.variantes.store', $producto) }}"
                      class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="talla" :value="__('Talla')" />
                        <x-text-input id="talla" name="talla" type="text" class="mt-1.5 w-32" :value="old('talla')" required />
                    </div>
                    <div>
                        <x-input-label for="color" :value="__('Color')" />
                        <x-text-input id="color" name="color" type="text" class="mt-1.5 w-32" :value="old('color')" required />
                    </div>
                    <div>
                        <x-input-label for="codigo" :value="__('Código (opcional)')" />
                        <x-text-input id="codigo" name="codigo" type="text" class="mt-1.5 w-40" :value="old('codigo')" />
                    </div>
                    <x-button>
                        <x-icon name="mas" class="size-4" />
                        {{ __('Agregar variante') }}
                    </x-button>
                </form>
                <x-input-error :messages="$errors->get('talla')" class="mt-2" />
                <x-input-error :messages="$errors->get('color')" class="mt-2" />
            </div>
        </x-card>

        <div>
            <a href="{{ route('admin.productos.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-ink-soft transition-colors hover:text-ink">
                <x-icon name="arrow-left" class="size-4" />
                {{ __('Volver a productos') }}
            </a>
        </div>
    </x-page>
</x-app-layout>
