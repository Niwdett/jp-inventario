<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $secciones = [
                'dashboard' => __('Dashboard'),
                'profile.' => __('Mi perfil'),
                'ventas.' => __('Ventas'),
                'admin.clientes.' => __('Clientes'),
                'admin.productos.' => __('Productos'),
                'admin.categorias.' => __('Categorías'),
                'admin.inventario.' => __('Inventario'),
                'admin.creditos.' => __('Crédito'),
                'admin.devoluciones.' => __('Devoluciones'),
                'admin.reportes.' => __('Reportes'),
                'admin.usuarios.' => __('Usuarios'),
            ];
            $rutaActual = request()->route()?->getName() ?? '';
            $seccionActual = collect($secciones)->first(
                fn ($label, $prefijo) => $rutaActual === $prefijo || str_starts_with($rutaActual, $prefijo)
            );
        @endphp
        <title>{{ $seccionActual ? $seccionActual.' · JP' : config('app.name', 'JP') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-ink">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-canvas">
            {{-- Fondo oscuro para la sidebar en móvil --}}
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" x-cloak
                 class="fixed inset-0 z-40 bg-ink/40 lg:hidden"></div>

            <x-layout.sidebar />

            <div class="lg:pl-64">
                <x-layout.topbar />

                @isset($header)
                    <header class="border-b border-line bg-surface">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-confirm-modal />
    </body>
</html>
