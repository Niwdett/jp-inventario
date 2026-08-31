<x-app-layout>
    <x-page :title="__('Editar producto')"
            :subtitle="$producto->nombre.' · '.$producto->codigo_interno">
        <x-card class="max-w-2xl">
            <form method="POST" action="{{ route('admin.productos.update', $producto) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.productos._form', ['producto' => $producto])
            </form>
        </x-card>
    </x-page>
</x-app-layout>
