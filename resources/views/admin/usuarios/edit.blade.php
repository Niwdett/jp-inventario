<x-app-layout>
    <x-page :title="__('Editar usuario')" :subtitle="$usuario->name">
        <x-card class="max-w-xl">
            <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}">
                @csrf
                @method('PUT')
                @include('admin.usuarios._form', ['usuario' => $usuario, 'roles' => $roles])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
