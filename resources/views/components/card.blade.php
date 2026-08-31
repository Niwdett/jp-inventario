@props([
    'title' => null,
    'subtitle' => null,
    /* true = sin padding en el cuerpo (para tablas a sangre) */
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-line bg-surface shadow-xs']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-3.5">
            <div class="min-w-0">
                @if ($title)
                    <h3 class="truncate text-sm font-semibold text-ink">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 truncate text-xs text-ink-faint">{{ $subtitle }}</p>
                @endif
            </div>
            @isset ($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['px-5 py-4' => ! $flush])>
        {{ $slot }}
    </div>
</div>
