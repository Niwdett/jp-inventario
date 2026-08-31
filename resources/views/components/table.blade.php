@props([
    'head' => null,
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full text-sm']) }}>
        @isset ($head)
            <thead class="border-b border-line bg-surface-sunken text-left text-xs font-medium uppercase tracking-wide text-ink-faint">
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody class="divide-y divide-line">
            {{ $slot }}
        </tbody>
        @isset ($foot)
            <tfoot class="border-t border-line bg-surface-sunken text-ink">
                {{ $foot }}
            </tfoot>
        @endisset
    </table>
</div>
