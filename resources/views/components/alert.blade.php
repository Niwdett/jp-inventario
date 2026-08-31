@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variants = [
        'success' => ['classes' => 'border-success-200 bg-success-50 text-success-700', 'icon' => 'exito'],
        'danger' => ['classes' => 'border-danger-200 bg-danger-50 text-danger-700', 'icon' => 'error'],
        'warning' => ['classes' => 'border-warning-200 bg-warning-50 text-warning-700', 'icon' => 'advertencia'],
        'info' => ['classes' => 'border-info-200 bg-info-50 text-info-700', 'icon' => 'info'],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$config['classes']]) }}>
    <x-icon :name="$config['icon']" class="mt-px size-4 shrink-0" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
</div>
