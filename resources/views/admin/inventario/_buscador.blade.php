@php($buscarActivo = filled(request('buscar')))

{{-- Búsqueda por producto compartida por Entradas, Ajustes y Movimientos. --}}
<x-card>
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="min-w-56 flex-1">
            <x-input-label for="buscar" :value="__('Buscar producto')" />
            <x-text-input id="buscar" name="buscar" type="search" class="mt-1.5 w-full"
                          :value="request('buscar')" placeholder="{{ __('Nombre o código del producto…') }}" />
        </div>
        <x-button>
            <x-icon name="filtro" class="size-4" />
            {{ __('Filtrar') }}
        </x-button>
        @if ($buscarActivo)
            <x-button variant="ghost" :href="url()->current()">{{ __('Limpiar') }}</x-button>
        @endif
    </form>
</x-card>
