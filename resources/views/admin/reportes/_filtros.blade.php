@php($comparable = $comparable ?? false)

<form method="GET" action="{{ route($ruta) }}" class="bg-white shadow sm:rounded-lg p-4 flex flex-wrap items-end gap-4">
    <div>
        <x-input-label :value="__('Periodo')" />
        <div class="mt-1 flex flex-wrap gap-2">
            @foreach (['hoy' => __('Hoy'), 'semana' => __('Esta semana'), 'mes' => __('Este mes')] as $valor => $texto)
                <a href="{{ route($ruta, array_filter(['preset' => $valor, 'comparar' => request('comparar')])) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-md text-sm border {{ $periodo['preset'] === $valor ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $texto }}
                </a>
            @endforeach
        </div>
    </div>

    <div>
        <x-input-label for="desde" :value="__('Desde')" />
        <x-text-input id="desde" name="desde" type="date" class="mt-1 block"
                      :value="old('desde', $periodo['preset'] === 'personalizado' ? $periodo['desde']->format('Y-m-d') : '')" />
    </div>
    <div>
        <x-input-label for="hasta" :value="__('Hasta')" />
        <x-text-input id="hasta" name="hasta" type="date" class="mt-1 block"
                      :value="old('hasta', $periodo['preset'] === 'personalizado' ? $periodo['hasta']->format('Y-m-d') : '')" />
    </div>

    <input type="hidden" name="preset" value="personalizado">

    @if ($comparable)
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="comparar" value="1" @checked($periodo['comparar'])
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            {{ __('Comparar con el periodo anterior') }}
        </label>
    @endif

    <x-primary-button>{{ __('Aplicar') }}</x-primary-button>

    <p class="w-full text-xs text-gray-500">{{ __('Mostrando:') }} <span class="font-medium">{{ $periodo['etiqueta'] }}</span></p>
    <x-input-error :messages="$errors->get('desde')" />
    <x-input-error :messages="$errors->get('hasta')" />
</form>
