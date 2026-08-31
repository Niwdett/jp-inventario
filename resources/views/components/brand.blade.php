@props([
    /* full | stacked | wordmark | mark */
    'variant' => 'full',
    /* Texto secundario bajo "JP". Pasa null/'' para ocultarlo. */
    'sublabel' => 'Inventario',
])

@php
    $showSub = filled($sublabel);
@endphp

@switch($variant)
    @case('mark')
        <x-application-logo {{ $attributes->merge(['class' => 'h-9 w-9']) }} />
        @break

    @case('stacked')
        <span {{ $attributes->merge(['class' => 'inline-flex flex-col items-center gap-2 text-center']) }}>
            <x-application-logo class="h-14 w-14" />
            <span class="font-display text-2xl font-bold leading-none tracking-tight text-navy-800">JP</span>
            @if ($showSub)
                <span class="text-[0.65rem] font-semibold uppercase tracking-[0.35em] text-ink-faint">{{ $sublabel }}</span>
            @endif
        </span>
        @break

    @case('wordmark')
        <span {{ $attributes->merge(['class' => 'inline-flex flex-col leading-none']) }}>
            <span class="font-display text-xl font-bold tracking-tight text-navy-800">JP</span>
            @if ($showSub)
                <span class="mt-1 text-[0.6rem] font-semibold uppercase tracking-[0.3em] text-ink-faint">{{ $sublabel }}</span>
            @endif
        </span>
        @break

    @default
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
            <x-application-logo class="h-9 w-9" />
            <span class="inline-flex flex-col leading-none">
                <span class="font-display text-lg font-bold tracking-tight text-navy-800">JP</span>
                @if ($showSub)
                    <span class="mt-0.5 text-[0.6rem] font-semibold uppercase tracking-[0.28em] text-ink-faint">{{ $sublabel }}</span>
                @endif
            </span>
        </span>
@endswitch
