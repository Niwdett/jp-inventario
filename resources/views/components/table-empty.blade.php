@props([
    'colspan' => 1,
    'icon' => 'bandeja',
    'title' => null,
    'tone' => 'neutral',
])

{{--
    Fila de tabla con un <x-empty-state> centrado. Uso dentro de @forelse:
      @empty
          <x-table-empty :colspan="6" icon="productos" :title="__('Aún no hay productos')">
              {{ __('Crea el primero para empezar.') }}
          </x-table-empty>
      @endforelse
--}}
<tr>
    <td colspan="{{ $colspan }}" class="p-0">
        <x-empty-state :icon="$icon" :title="$title" :tone="$tone" compact>
            {{ $slot }}
            @isset ($actions)
                <x-slot:actions>{{ $actions }}</x-slot:actions>
            @endisset
        </x-empty-state>
    </td>
</tr>
