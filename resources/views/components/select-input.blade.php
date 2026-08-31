@props([
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

<select {{ $attributes->merge(['class' => 'block w-full rounded-lg border-line bg-surface text-ink shadow-xs transition-colors focus:border-primary-500 focus:ring-2 focus:ring-primary-200 disabled:cursor-not-allowed disabled:bg-surface-sunken']) }}>
    @if (! is_null($placeholder))
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) old($attributes->get('name'), $selected) === (string) $value)>
            {{ $label }}
        </option>
    @endforeach
</select>
