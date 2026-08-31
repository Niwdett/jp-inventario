<x-guest-layout>
    <x-slot name="heading">{{ __('Iniciar sesión') }}</x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center gap-2 text-sm text-ink-soft">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-line text-primary-600 shadow-xs focus:ring-2 focus:ring-primary-200">
                {{ __('Recordarme') }}
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-primary-700 transition-colors hover:text-primary-800"
                   href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <x-button class="w-full">{{ __('Entrar') }}</x-button>
    </form>
</x-guest-layout>
