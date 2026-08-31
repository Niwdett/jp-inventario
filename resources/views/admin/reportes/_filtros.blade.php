@php($comparable = $comparable ?? false)

<x-card>
    <form method="GET" action="{{ route($ruta) }}" class="flex flex-wrap items-end gap-4">
        <div>
            <x-input-label :value="__('Periodo')" />
            <div class="mt-1.5 flex flex-wrap gap-2">
                @foreach (['hoy' => __('Hoy'), 'semana' => __('Esta semana'), 'mes' => __('Este mes')] as $valor => $texto)
                    <a href="{{ route($ruta, array_filter(['preset' => $valor, 'comparar' => request('comparar')])) }}"
                       @class([
                           'inline-flex items-center rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors',
                           'border-primary-600 bg-primary-600 text-white' => $periodo['preset'] === $valor,
                           'border-line bg-surface text-ink-soft hover:bg-surface-sunken' => $periodo['preset'] !== $valor,
                       ])>{{ $texto }}</a>
                @endforeach
            </div>
        </div>

        <div>
            <x-input-label for="desde" :value="__('Desde')" />
            <x-text-input id="desde" name="desde" type="date" class="mt-1.5"
                          :value="old('desde', $periodo['preset'] === 'personalizado' ? $periodo['desde']->format('Y-m-d') : '')" />
        </div>
        <div>
            <x-input-label for="hasta" :value="__('Hasta')" />
            <x-text-input id="hasta" name="hasta" type="date" class="mt-1.5"
                          :value="old('hasta', $periodo['preset'] === 'personalizado' ? $periodo['hasta']->format('Y-m-d') : '')" />
        </div>

        <input type="hidden" name="preset" value="personalizado">

        @if ($comparable)
            <label class="inline-flex items-center gap-2 pb-1.5 text-sm text-ink-soft">
                <input type="checkbox" name="comparar" value="1" @checked($periodo['comparar'])
                       class="rounded border-line text-primary-600 focus:ring-2 focus:ring-primary-200">
                {{ __('Comparar con el periodo anterior') }}
            </label>
        @endif

        <x-button>{{ __('Aplicar') }}</x-button>

        <p class="w-full text-xs text-ink-faint">{{ __('Mostrando:') }} <span class="font-medium text-ink-soft">{{ $periodo['etiqueta'] }}</span></p>
        <x-input-error :messages="$errors->get('desde')" />
        <x-input-error :messages="$errors->get('hasta')" />
    </form>
</x-card>
