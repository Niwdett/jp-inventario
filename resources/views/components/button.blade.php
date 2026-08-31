@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition active:scale-[.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas disabled:pointer-events-none disabled:opacity-50';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-sm',
    ];

    $variants = [
        'primary' => 'bg-primary-600 text-white shadow-xs hover:bg-primary-700 active:bg-primary-800',
        'secondary' => 'border border-line bg-surface text-ink shadow-xs hover:bg-surface-sunken',
        'danger' => 'bg-danger-600 text-white shadow-xs hover:bg-danger-700 active:bg-danger-800',
        'ghost' => 'text-ink-soft hover:bg-surface-sunken hover:text-ink',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    {{--
        Botón de envío: al enviar el formulario (válido y sin cancelar) se
        deshabilita y muestra un spinner, evitando el doble submit. Se desactiva
        con el atributo `no-loading`.
    --}}
    <button
        {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}
        @unless ($attributes->has('no-loading'))
            x-data="{ loading: false }"
            x-init="
                const form = $el.closest('form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        if (! e.defaultPrevented) { loading = true; }
                    });
                }
            "
            x-bind:disabled="loading"
            x-bind:aria-busy="loading"
        @endunless
    >
        @unless ($attributes->has('no-loading'))
            <svg x-show="loading" x-cloak class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span x-show="! loading" class="contents">{{ $slot }}</span>
        @else
            {{ $slot }}
        @endunless
    </button>
@endif
