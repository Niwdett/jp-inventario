@props([
    'title' => null,
    'subtitle' => null,
])

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    @if ($title || $subtitle || isset($actions) || isset($heading))
        <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                @isset ($heading)
                    {{ $heading }}
                @elseif ($title)
                    <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $title }}</h1>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-ink-soft">{{ $subtitle }}</p>
                @endif
            </div>
            @isset ($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div {{ $attributes->merge(['class' => 'space-y-6']) }}>
        {{ $slot }}
    </div>
</div>
