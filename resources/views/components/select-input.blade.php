@props([
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

<select {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
    @if (! is_null($placeholder))
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) old($attributes->get('name'), $selected) === (string) $value)>
            {{ $label }}
        </option>
    @endforeach
</select>
