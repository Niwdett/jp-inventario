@props([
    'label' => '',
    'value' => '',
    'hint' => null,
    'icon' => null,
    /* neutral | positive | negative | warning */
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'text-ink',
        'positive' => 'text-success-700',
        'negative' => 'text-danger-600',
        'warning' => 'text-warning-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface p-4 shadow-xs']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-medium uppercase tracking-wide text-ink-faint">{{ $label }}</p>
        @if ($icon)
            <x-icon :name="$icon" class="size-4 text-ink-faint" />
        @endif
    </div>
    <p class="mt-1.5 text-2xl font-semibold tabular-nums {{ $tones[$tone] ?? $tones['neutral'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-faint">{{ $hint }}</p>
    @endif
</div>
