@props([
    'variant' => 'neutral',
    'dot' => false,
])

@php
    $variants = [
        'neutral' => 'bg-surface-sunken text-ink-soft ring-line',
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-200',
        'success' => 'bg-success-50 text-success-700 ring-success-200',
        'warning' => 'bg-warning-50 text-warning-700 ring-warning-200',
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-200',
        'info' => 'bg-info-50 text-info-700 ring-info-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.($variants[$variant] ?? $variants['neutral'])]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full bg-current opacity-70"></span>
    @endif
    {{ $slot }}
</span>
