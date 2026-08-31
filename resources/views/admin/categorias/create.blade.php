<x-app-layout>
    <x-page :title="__('Nueva categoría')">
        <x-card class="max-w-xl">
            <form method="POST" action="{{ route('admin.categorias.store') }}">
                @csrf
                @include('admin.categorias._form', ['categoria' => null])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
