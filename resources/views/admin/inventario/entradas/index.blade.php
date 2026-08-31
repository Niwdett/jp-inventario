<x-app-layout>
    <x-page :title="__('Entradas de mercancía')">
        <x-slot name="actions">
            <x-button :href="route('admin.inventario.entradas.create')">
                <x-icon name="mas" class="size-4" />
                {{ __('Registrar entrada') }}
            </x-button>
        </x-slot>

        @include('admin.inventario._nav')

        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert variant="danger">{{ session('error') }}</x-alert>
        @endif

        <x-card flush x-data="{ anulando: null }">
            <x-table>
                <x-slot name="head">
                    <th class="px-5 py-3 font-medium">{{ __('Fecha') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Variante') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Cantidad') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Costo unitario') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Proveedor') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Registró') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Acciones') }}</th>
                </x-slot>

                @forelse ($entradas as $entrada)
                    <tr @class(['transition-colors', 'text-ink-faint' => ! $entrada->esAnulable(), 'hover:bg-surface-sunken/60' => $entrada->esAnulable()])>
                        <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $entrada->fecha->format('Y-m-d') }}</td>
                        <td class="px-5 py-3">
                            <span class="text-ink">{{ $entrada->variante->producto->nombre }}</span>
                            <span class="text-ink-faint">— {{ $entrada->variante->etiqueta() }}</span>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-success-700">+{{ $entrada->cantidad }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-ink-soft">{{ number_format((float) $entrada->costo_unitario, 4) }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $entrada->proveedor ?: '—' }}</td>
                        <td class="px-5 py-3 text-ink-soft">{{ $entrada->usuario->name }}</td>
                        <td class="px-5 py-3 text-right">
                            @if ($entrada->esAnulable())
                                <button type="button"
                                        x-on:click="anulando = (anulando === {{ $entrada->id }} ? null : {{ $entrada->id }})"
                                        class="text-sm font-medium text-danger-600 transition-colors hover:text-danger-700">
                                    {{ __('Anular') }}
                                </button>
                            @else
                                <x-badge title="{{ $entrada->anulada_at->format('Y-m-d H:i') }} · {{ $entrada->anuladaPor?->name ?? '—' }} · {{ $entrada->motivo_anulacion }}">
                                    {{ __('Anulada') }}
                                </x-badge>
                            @endif
                        </td>
                    </tr>
                    @if ($entrada->esAnulable())
                        <tr x-show="anulando === {{ $entrada->id }}" style="display: none;">
                            <td colspan="7" class="border-l-2 border-danger-300 bg-danger-50 px-5 py-4">
                                <form method="POST" action="{{ route('admin.inventario.entradas.anular', $entrada) }}"
                                      class="flex flex-col gap-3 sm:flex-row sm:items-start"
                                      onsubmit="return confirm('{{ __('Anular esta entrada y recalcular el costo promedio de la variante. ¿Continuar?') }}')">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex-1">
                                        <label for="motivo-{{ $entrada->id }}" class="block text-xs font-medium text-ink-soft">
                                            {{ __('Motivo de la anulación') }}
                                        </label>
                                        <textarea id="motivo-{{ $entrada->id }}" name="motivo" rows="2" required minlength="3" maxlength="255"
                                                  class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-ink shadow-xs focus:border-danger-400 focus:ring-2 focus:ring-danger-200">{{ old('motivo') }}</textarea>
                                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                                    </div>
                                    <x-button variant="danger" class="sm:mt-5">{{ __('Anular entrada') }}</x-button>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-ink-faint">{{ __('Aún no hay entradas registradas.') }}</td></tr>
                @endforelse
            </x-table>
        </x-card>

        {{ $entradas->links() }}
    </x-page>
</x-app-layout>
