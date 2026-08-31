@props([
    'value' => 0,
    'decimals' => 2,
    'symbol' => false,
])

<span {{ $attributes->class('tabular-nums whitespace-nowrap') }}>{{ money($value, (int) $decimals, (bool) $symbol) }}</span>
