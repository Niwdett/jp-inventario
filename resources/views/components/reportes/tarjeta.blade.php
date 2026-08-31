@props([
    'titulo' => '',
    'valor' => '',
    'detalle' => null,
    'tono' => 'neutral',
])

@php
    $tonos = [
        'neutral' => 'text-ink',
        'positivo' => 'text-success-700',
        'negativo' => 'text-danger-600',
        'alerta' => 'text-warning-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface p-4 shadow-xs transition-shadow']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-ink-faint">{{ $titulo }}</p>
    <p class="mt-1.5 text-2xl font-semibold tabular-nums {{ $tonos[$tono] ?? $tonos['neutral'] }}">{{ $valor }}</p>
    @if ($detalle)
        <p class="mt-1 text-xs text-ink-faint">{{ $detalle }}</p>
    @endif
</div>
