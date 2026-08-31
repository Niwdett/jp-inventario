<x-tabs>
    <x-tabs.link :href="route('admin.inventario.entradas.index')" :active="request()->routeIs('admin.inventario.entradas.*')">{{ __('Entradas') }}</x-tabs.link>
    <x-tabs.link :href="route('admin.inventario.ajustes.index')" :active="request()->routeIs('admin.inventario.ajustes.*')">{{ __('Ajustes') }}</x-tabs.link>
    <x-tabs.link :href="route('admin.inventario.movimientos.index')" :active="request()->routeIs('admin.inventario.movimientos.*')">{{ __('Movimientos') }}</x-tabs.link>
    <x-tabs.link :href="route('admin.inventario.alertas.index')" :active="request()->routeIs('admin.inventario.alertas.*')">{{ __('Alertas de stock') }}</x-tabs.link>
</x-tabs>
