@props([
    'href',
    'icon',
    'active' => false,
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300',
       'bg-primary-600 text-white shadow-xs' => $active,
       'text-ink-soft hover:bg-primary-50 hover:text-primary-700' => ! $active,
   ]) }}>
    <x-icon :name="$icon" @class([
        'size-5 shrink-0',
        'text-white' => $active,
        'text-ink-faint group-hover:text-primary-600' => ! $active,
    ]) />
    <span>{{ $slot }}</span>
</a>
