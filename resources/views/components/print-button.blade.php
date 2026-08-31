{{-- Botón "Imprimir": abre el diálogo de impresión y no aparece en el papel. --}}
<x-button variant="secondary" type="button" no-loading
          onclick="window.print()"
          {{ $attributes->class('print:hidden') }}>
    <x-icon name="imprimir" class="size-4" />
    {{ $slot->isNotEmpty() ? $slot : __('Imprimir') }}
</x-button>
