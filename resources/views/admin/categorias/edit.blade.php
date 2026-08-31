<x-app-layout>
    <x-page :title="__('Editar categoría')" :subtitle="$categoria->nombre">
        <x-card class="max-w-xl">
            <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}">
                @csrf
                @method('PUT')
                @include('admin.categorias._form', ['categoria' => $categoria])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
