@props([
    'head' => null,
    /* true = en móvil (<640px) cada fila se apila como tarjeta (ver app.css) */
    'stack' => false,
])

<div @class(['overflow-x-auto' => ! $stack])>
    <table {{ $attributes->merge(['class' => 'min-w-full text-sm'.($stack ? ' table-stack' : '')]) }}>
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
