@props([
    'icon',
    'label',
    'href' => null,
    /* default | danger */
    'variant' => 'default',
])

@php
    $variants = [
        'default' => 'text-ink-faint hover:bg-surface-sunken hover:text-ink',
        'danger' => 'text-ink-faint hover:bg-danger-50 hover:text-danger-600',
    ];

    $classes = 'inline-flex items-center justify-center rounded-lg p-1.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 '.($variants[$variant] ?? $variants['default']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes, 'title' => $label, 'aria-label' => $label]) }}>
        <x-icon :name="$icon" class="size-4" />
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes, 'title' => $label, 'aria-label' => $label]) }}>
        <x-icon :name="$icon" class="size-4" />
    </button>
@endif
