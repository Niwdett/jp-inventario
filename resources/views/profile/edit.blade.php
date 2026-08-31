<x-app-layout>
    <x-page :title="__('Mi perfil')" :subtitle="__('Gestiona tu contraseña de acceso.')">
        <x-card class="max-w-xl">
            @include('profile.partials.update-password-form')
        </x-card>
    </x-page>
</x-app-layout>
