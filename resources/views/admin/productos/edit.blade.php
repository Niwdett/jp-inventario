<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar producto') }} — {{ $producto->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="mb-4 text-sm text-gray-500">
                    {{ __('Código interno') }}: <span class="font-mono">{{ $producto->codigo_interno }}</span>
                    <span class="text-gray-400">({{ __('no editable') }})</span>
                </p>
                <form method="POST" action="{{ route('admin.productos.update', $producto) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.productos._form', ['producto' => $producto])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
