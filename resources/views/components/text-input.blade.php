@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-lg border-line bg-surface text-ink shadow-xs transition-colors placeholder:text-ink-faint focus:border-primary-500 focus:ring-2 focus:ring-primary-200 disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:text-ink-faint']) }}>
