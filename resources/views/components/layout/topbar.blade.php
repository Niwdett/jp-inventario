@php
    $user = auth()->user();
    $iniciales = collect(explode(' ', trim($user?->name ?? '')))
        ->filter()
        ->take(2)
        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
        ->join('');
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-surface/95 px-4 backdrop-blur-sm sm:px-6 lg:px-8 print:hidden">
    <button type="button" @click="sidebarOpen = true"
            class="-ml-1.5 rounded-lg p-1.5 text-ink-soft transition-colors hover:bg-surface-sunken hover:text-ink lg:hidden">
        <x-icon name="menu" class="size-5" />
        <span class="sr-only">{{ __('Abrir menú') }}</span>
    </button>

    <div class="flex-1"></div>

    <x-dropdown align="right" width="48" contentClasses="py-1 bg-surface">
        <x-slot name="trigger">
            <button type="button"
                    class="flex items-center gap-2 rounded-lg py-1.5 pl-1.5 pr-2 text-sm text-ink-soft transition-colors hover:bg-surface-sunken">
                <span class="flex size-8 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700">
                    {{ $iniciales ?: 'JP' }}
                </span>
                <span class="hidden font-medium text-ink sm:block">{{ $user?->name }}</span>
                <x-icon name="chevron-down" class="size-4 text-ink-faint" />
            </button>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')">{{ __('Mi perfil') }}</x-dropdown-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                                 onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Cerrar sesión') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</header>
