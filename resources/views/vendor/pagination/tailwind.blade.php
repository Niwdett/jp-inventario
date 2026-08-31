@php
    $base = 'inline-flex items-center border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink-soft transition-colors hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300';
    $disabled = 'inline-flex items-center border border-line bg-surface-sunken px-3.5 py-2 text-sm font-medium text-ink-faint';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-3">
        <p class="hidden text-sm text-ink-faint sm:block">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-medium text-ink-soft">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-ink-soft">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!} <span class="font-medium text-ink-soft">{{ $paginator->total() }}</span> {!! __('results') !!}
        </p>

        <span class="isolate inline-flex rounded-lg shadow-xs">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }} rounded-l-lg" aria-hidden="true">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} rounded-l-lg" aria-label="{{ __('pagination.previous') }}">{!! __('pagination.previous') !!}</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $disabled }} -ml-px" aria-disabled="true">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="-ml-px inline-flex items-center border border-primary-600 bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $base }} -ml-px" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} -ml-px rounded-r-lg" aria-label="{{ __('pagination.next') }}">{!! __('pagination.next') !!}</a>
            @else
                <span class="{{ $disabled }} -ml-px rounded-r-lg" aria-hidden="true">{!! __('pagination.next') !!}</span>
            @endif
        </span>
    </nav>
@endif
