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
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
