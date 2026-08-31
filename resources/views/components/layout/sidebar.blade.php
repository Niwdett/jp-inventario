@php
    $user = auth()->user();
    $esAdmin = $user?->esAdministrador() ?? false;

    $iniciales = collect(explode(' ', trim($user?->name ?? '')))
        ->filter()
        ->take(2)
        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
        ->join('');
@endphp

<aside x-cloak
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-surface transition-transform duration-200 ease-out lg:translate-x-0 print:hidden">

    {{-- Marca --}}
    <div class="flex h-16 items-center justify-between border-b border-line px-4">
        <a href="{{ route('dashboard') }}" class="rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300">
            <x-brand />
        </a>
        <button type="button" @click="sidebarOpen = false"
                class="rounded-lg p-1.5 text-ink-faint hover:bg-surface-sunken hover:text-ink lg:hidden">
            <x-icon name="cerrar" class="size-5" />
            <span class="sr-only">{{ __('Cerrar menú') }}</span>
        </button>
    </div>

    {{-- Navegación --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        <div class="space-y-1">
            <p class="px-3 pb-1 text-[0.68rem] font-semibold uppercase tracking-wider text-ink-faint">{{ __('General') }}</p>
            <x-layout.nav-item :href="route('dashboard')" icon="dashboard" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-layout.nav-item>
            <x-layout.nav-item :href="route('ventas.index')" icon="ventas" :active="request()->routeIs('ventas.*')">
                {{ __('Ventas') }}
            </x-layout.nav-item>
            <x-layout.nav-item :href="route('admin.clientes.index')" icon="clientes" :active="request()->routeIs('admin.clientes.*')">
                {{ __('Clientes') }}
            </x-layout.nav-item>
        </div>

        @if ($esAdmin)
            <div class="space-y-1">
                <p class="px-3 pb-1 text-[0.68rem] font-semibold uppercase tracking-wider text-ink-faint">{{ __('Administración') }}</p>
                <x-layout.nav-item :href="route('admin.productos.index')" icon="productos" :active="request()->routeIs('admin.productos.*')">
                    {{ __('Productos') }}
                </x-layout.nav-item>
                <x-layout.nav-item :href="route('admin.categorias.index')" icon="categorias" :active="request()->routeIs('admin.categorias.*')">
                    {{ __('Categorías') }}
                </x-layout.nav-item>
                <x-layout.nav-item :href="route('admin.inventario.entradas.index')" icon="inventario" :active="request()->routeIs('admin.inventario.*')">
                    {{ __('Inventario') }}
                </x-layout.nav-item>
                <x-layout.nav-item :href="route('admin.creditos.index')" icon="credito" :active="request()->routeIs('admin.creditos.*')">
                    {{ __('Crédito') }}
                </x-layout.nav-item>
                <x-layout.nav-item :href="route('admin.devoluciones.index')" icon="devoluciones" :active="request()->routeIs('admin.devoluciones.*')">
                    {{ __('Devoluciones') }}
                </x-layout.nav-item>
                <x-layout.nav-item :href="route('admin.reportes.ventas')" icon="reportes" :active="request()->routeIs('admin.reportes.*')">
                    {{ __('Reportes') }}
                </x-layout.nav-item>
                <x-layout.nav-item :href="route('admin.usuarios.index')" icon="usuarios" :active="request()->routeIs('admin.usuarios.*')">
                    {{ __('Usuarios') }}
                </x-layout.nav-item>
            </div>
        @endif
    </nav>

    {{-- Usuario --}}
    <div class="border-t border-line p-3">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700">
                {{ $iniciales ?: 'JP' }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink">{{ $user?->name }}</p>
                <p class="truncate text-xs text-ink-faint">{{ $user?->rol?->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-1.5 text-ink-faint transition-colors hover:bg-danger-50 hover:text-danger-600"
                        title="{{ __('Cerrar sesión') }}">
                    <x-icon name="salir" class="size-5" />
                    <span class="sr-only">{{ __('Cerrar sesión') }}</span>
                </button>
            </form>
        </div>
    </div>
</aside>
