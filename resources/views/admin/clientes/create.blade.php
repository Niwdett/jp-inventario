<x-app-layout>
    <x-page :title="__('Nuevo cliente')">
        <x-card class="max-w-xl">
            <form method="POST" action="{{ route('admin.clientes.store') }}">
                @csrf
                @include('admin.clientes._form', ['cliente' => null])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
