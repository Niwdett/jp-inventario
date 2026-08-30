@props([
    'titulo' => '',
    'valor' => '',
    'detalle' => null,
    'tono' => 'neutral',
])

@php
    $tonos = [
        'neutral' => 'text-gray-900',
        'positivo' => 'text-green-700',
        'negativo' => 'text-red-700',
        'alerta' => 'text-amber-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white shadow sm:rounded-lg p-4']) }}>
    <p class="text-xs uppercase tracking-wider text-gray-500">{{ $titulo }}</p>
    <p class="mt-1 text-2xl font-semibold {{ $tonos[$tono] ?? $tonos['neutral'] }}">{{ $valor }}</p>
    @if ($detalle)
        <p class="mt-1 text-xs text-gray-400">{{ $detalle }}</p>
    @endif
</div>
