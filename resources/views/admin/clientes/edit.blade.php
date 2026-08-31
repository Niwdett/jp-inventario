<x-app-layout>
    <x-page :title="__('Editar cliente')" :subtitle="$cliente->nombre">
        <x-card class="max-w-xl">
            <form method="POST" action="{{ route('admin.clientes.update', $cliente) }}">
                @csrf
                @method('PUT')
                @include('admin.clientes._form', ['cliente' => $cliente])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
