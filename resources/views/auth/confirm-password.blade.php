<x-guest-layout>
    <x-slot name="heading">{{ __('Confirma tu contraseña') }}</x-slot>
    <x-slot name="description">{{ __('Esta es un área segura. Vuelve a introducir tu contraseña para continuar.') }}</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <x-button class="w-full">{{ __('Confirmar') }}</x-button>
    </form>
</x-guest-layout>
