<x-app-layout>
    <x-page :title="__('Nuevo producto')">
        <x-card class="max-w-2xl">
            <form method="POST" action="{{ route('admin.productos.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.productos._form', ['producto' => null, 'categorias' => $categorias])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
