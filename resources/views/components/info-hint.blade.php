@props([
    'title' => null,
    /* alineación del panel respecto al icono */
    'align' => 'left',
])

@php
    $origin = $align === 'right' ? 'end-0' : 'start-0';
@endphp

{{--
    Ayuda contextual sutil: un icono de información que revela un panel breve al
    pasar el cursor o al enfocar el botón (teclado / toque). Solo CSS, sin estado
    ni JS — funciona en cualquier contexto (incluidas listas repetidas con x-for).
--}}
<span class="group relative inline-flex align-middle">
    <button type="button"
            class="peer inline-flex size-4 items-center justify-center rounded-full text-ink-faint transition-colors hover:text-ink-soft focus-visible:text-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
            aria-label="{{ $title ?? __('Más información') }}">
        <x-icon name="info" class="size-3.5" />
    </button>

    <span role="tooltip"
          class="pointer-events-none absolute top-6 z-30 w-56 translate-y-1 rounded-lg border border-line bg-surface p-3 text-left text-xs font-normal normal-case leading-relaxed text-ink-soft opacity-0 shadow-lg transition duration-150 {{ $origin }} group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 peer-focus-visible:pointer-events-auto peer-focus-visible:translate-y-0 peer-focus-visible:opacity-100">
        @if ($title)
            <span class="mb-0.5 block font-semibold text-ink">{{ $title }}</span>
        @endif
        {{ $slot }}
    </span>
</span>
