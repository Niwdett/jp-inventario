<x-tabs>
    <x-tabs.link :href="route('admin.reportes.ventas')" :active="request()->routeIs('admin.reportes.ventas')">{{ __('Ventas por periodo') }}</x-tabs.link>
    <x-tabs.link :href="route('admin.reportes.inventario')" :active="request()->routeIs('admin.reportes.inventario')">{{ __('Inventario disponible') }}</x-tabs.link>
    <x-tabs.link :href="route('admin.reportes.ganancias')" :active="request()->routeIs('admin.reportes.ganancias')">{{ __('Ganancias') }}</x-tabs.link>
</x-tabs>
