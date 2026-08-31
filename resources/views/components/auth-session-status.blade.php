@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-700']) }}>
        {{ $status }}
    </div>
@endif
