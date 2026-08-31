<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($heading) ? $heading.' · JP' : config('app.name', 'JP') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-canvas px-4 py-10">
            <div class="w-full max-w-sm">
                <a href="/" class="mx-auto mb-8 flex w-fit flex-col items-center gap-3">
                    <x-application-logo class="h-14 w-14" />
                    <span class="flex flex-col items-center leading-none">
                        <span class="font-display text-3xl font-bold tracking-tight text-navy-800">JP</span>
                        <span class="mt-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.4em] text-ink-faint">
                            {{ __('Ropa & Calzado') }}
                        </span>
                    </span>
                </a>

                <div class="rounded-2xl border border-line bg-surface p-6 shadow-sm sm:p-8">
                    @isset ($heading)
                        <div class="mb-6">
                            <h1 class="text-lg font-semibold text-ink">{{ $heading }}</h1>
                            @isset ($description)
                                <p class="mt-1 text-sm text-ink-soft">{{ $description }}</p>
                            @endisset
                        </div>
                    @endisset

                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-ink-faint">{{ __('JP · Sistema de inventario y ventas') }}</p>
            </div>
        </div>
    </body>
</html>
