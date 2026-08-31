<x-guest-layout>
    <x-slot name="heading">{{ __('Recuperar contraseña') }}</x-slot>
    <x-slot name="description">{{ __('Introduce tu correo y te enviaremos un enlace para elegir una nueva contraseña.') }}</x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="mt-1.5" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-button class="w-full">{{ __('Enviar enlace') }}</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        <a href="{{ route('login') }}" class="font-medium text-primary-700 transition-colors hover:text-primary-800">
            {{ __('Volver a iniciar sesión') }}
        </a>
    </p>
</x-guest-layout>
