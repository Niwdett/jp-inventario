<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Entradas de mercancía') }}</h2>
            <a href="{{ route('admin.inventario.entradas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Registrar entrada') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('admin.inventario._nav')

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm" x-data="{ anulando: null }">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('Fecha') }}</th>
                            <th class="px-6 py-3">{{ __('Variante') }}</th>
                            <th class="px-6 py-3">{{ __('Cantidad') }}</th>
                            <th class="px-6 py-3">{{ __('Costo unitario') }}</th>
                            <th class="px-6 py-3">{{ __('Proveedor') }}</th>
                            <th class="px-6 py-3">{{ __('Registró') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($entradas as $entrada)
                            <tr @class(['text-gray-400 bg-gray-50' => ! $entrada->esAnulable()])>
                                <td class="px-6 py-4">{{ $entrada->fecha->format('Y-m-d') }}</td>
                                <td class="px-6 py-4">
                                    {{ $entrada->variante->producto->nombre }}
                                    <span class="text-gray-400">— {{ $entrada->variante->etiqueta() }}</span>
                                </td>
                                <td class="px-6 py-4">+{{ $entrada->cantidad }}</td>
                                <td class="px-6 py-4">{{ number_format((float) $entrada->costo_unitario, 4) }}</td>
                                <td class="px-6 py-4">{{ $entrada->proveedor ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $entrada->usuario->name }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($entrada->esAnulable())
                                        <button type="button"
                                                x-on:click="anulando = (anulando === {{ $entrada->id }} ? null : {{ $entrada->id }})"
                                                class="text-red-600 hover:text-red-800 font-medium">
                                            {{ __('Anular') }}
                                        </button>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600"
                                              title="{{ $entrada->anulada_at->format('Y-m-d H:i') }} · {{ $entrada->anuladaPor?->name ?? '—' }} · {{ $entrada->motivo_anulacion }}">
                                            {{ __('Anulada') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @if ($entrada->esAnulable())
                                <tr x-show="anulando === {{ $entrada->id }}" style="display: none;">
                                    <td colspan="7" class="px-6 py-4 bg-red-50">
                                        <form method="POST" action="{{ route('admin.inventario.entradas.anular', $entrada) }}"
                                              class="flex flex-col sm:flex-row sm:items-start gap-3"
                                              onsubmit="return confirm('{{ __('Anular esta entrada y recalcular el costo promedio de la variante. ¿Continuar?') }}')">
                                            @csrf
                                            @method('PATCH')
                                            <div class="flex-1">
                                                <label for="motivo-{{ $entrada->id }}" class="block text-xs font-medium text-gray-600">
                                                    {{ __('Motivo de la anulación') }}
                                                </label>
                                                <textarea id="motivo-{{ $entrada->id }}" name="motivo" rows="2" required minlength="3" maxlength="255"
                                                          class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm">{{ old('motivo') }}</textarea>
                                                <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                                            </div>
                                            <x-danger-button class="sm:mt-5">{{ __('Anular entrada') }}</x-danger-button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">{{ __('Aún no hay entradas registradas.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $entradas->links() }}</div>
        </div>
    </div>
</x-app-layout>
