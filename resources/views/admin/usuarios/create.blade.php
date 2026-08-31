<x-app-layout>
    <x-page :title="__('Nuevo usuario')">
        <x-card class="max-w-xl">
            <form method="POST" action="{{ route('admin.usuarios.store') }}">
                @csrf
                @include('admin.usuarios._form', ['usuario' => null, 'roles' => $roles])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
