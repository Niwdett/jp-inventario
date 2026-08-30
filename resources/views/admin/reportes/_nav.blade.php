@php($tab = fn (string $pattern) => request()->routeIs($pattern)
    ? 'border-indigo-500 text-gray-900'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300')

<nav class="flex gap-6 border-b border-gray-200 text-sm font-medium">
    <a href="{{ route('admin.reportes.ventas') }}" class="py-3 border-b-2 {{ $tab('admin.reportes.ventas') }}">{{ __('Ventas por periodo') }}</a>
    <a href="{{ route('admin.reportes.inventario') }}" class="py-3 border-b-2 {{ $tab('admin.reportes.inventario') }}">{{ __('Inventario disponible') }}</a>
    <a href="{{ route('admin.reportes.ganancias') }}" class="py-3 border-b-2 {{ $tab('admin.reportes.ganancias') }}">{{ __('Ganancias') }}</a>
</nav>
