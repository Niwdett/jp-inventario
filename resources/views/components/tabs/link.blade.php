@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       '-mb-px shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors',
       'border-primary-600 text-ink' => $active,
       'border-transparent text-ink-faint hover:border-line-strong hover:text-ink' => ! $active,
   ]) }}>
    {{ $slot }}
</a>
