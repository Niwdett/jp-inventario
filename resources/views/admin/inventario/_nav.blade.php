@php($tab = fn (string $pattern) => request()->routeIs($pattern)
    ? 'border-indigo-500 text-gray-900'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300')

<nav class="flex gap-6 border-b border-gray-200 text-sm font-medium">
    <a href="{{ route('admin.inventario.entradas.index') }}" class="py-3 border-b-2 {{ $tab('admin.inventario.entradas.*') }}">{{ __('Entradas') }}</a>
    <a href="{{ route('admin.inventario.ajustes.index') }}" class="py-3 border-b-2 {{ $tab('admin.inventario.ajustes.*') }}">{{ __('Ajustes') }}</a>
    <a href="{{ route('admin.inventario.movimientos.index') }}" class="py-3 border-b-2 {{ $tab('admin.inventario.movimientos.*') }}">{{ __('Movimientos') }}</a>
    <a href="{{ route('admin.inventario.alertas.index') }}" class="py-3 border-b-2 {{ $tab('admin.inventario.alertas.*') }}">{{ __('Alertas de stock') }}</a>
</nav>
