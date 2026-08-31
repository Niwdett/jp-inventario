@props([
    'icon' => 'bandeja',
    'title' => null,
    /* true = versión reducida para tablas dentro de una ficha o el dashboard */
    'compact' => false,
    /* neutral | positive (buena noticia: no hay nada que atender) */
    'tone' => 'neutral',
])

@php
    $iconWrap = $tone === 'positive'
        ? 'bg-success-50 text-success-600'
        : 'bg-surface-sunken text-ink-faint';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center '.($compact ? 'gap-2 px-6 py-10' : 'gap-3 px-6 py-16')]) }}>
    <span @class([
        'flex items-center justify-center rounded-full',
        $iconWrap,
        'size-10' => $compact,
        'size-14' => ! $compact,
    ])>
        <x-icon :name="$icon" @class(['size-5' => $compact, 'size-6' => ! $compact]) />
    </span>

    @if ($title)
        <p @class(['font-medium text-ink', 'text-sm' => $compact, 'text-base' => ! $compact])>{{ $title }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <p class="max-w-sm text-sm text-ink-soft">{{ $slot }}</p>
    @endif

    @isset ($actions)
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
