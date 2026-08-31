---
paths:
  - 'resources/**'
---

# Resources

## Design System JP (rediseño visual) — tokens y marca
Rediseño visual en curso (etapas). Fuente de verdad del estilo: `resources/css/app.css` con `@theme` (Tailwind v4, sin tailwind.config.js).
- Base clara y aireada: fondo `--color-canvas #f6f7f9` (frío, casi blanco — NO crema), superficies blancas, `line` finísima. Texto `ink`/`ink-soft`/`ink-faint`.
- Color interactivo = `primary-*` (azul, `primary-600 #4a53c8`): nav activo, botones de acción, foco. El azul tinta `navy-*` se reserva para el logotipo y titulares. El oro `gold-*` es puntual (marca/detalles), no se usa para llenar áreas.
- Estados: `success|warning|danger|info` (escalas 50/100/200/600/700).
- Tipografía: UI = `font-sans` (Instrument Sans); marca "JP" y títulos = `font-display` (Playfair Display). Ambas self-hosted vía `bunny()` en vite.config.js + `@fonts` en los layouts (NO usar `<link>` a fonts.bunny.net).
- Marca: `<x-brand>` (variants full|stacked|wordmark|mark) y `<x-application-logo>` (sello temporal; sustituible por SVG oficial en public/img/brand/).
- Iconos: `<x-icon name="..." />` (set curado en resources/views/components/icon.blade.php). NO instalar librería de iconos.
- Shell (`layouts/app.blade.php`): sidebar izquierda blanca (`components/layout/sidebar`) + topbar (`components/layout/topbar`) + `<main>`. Estado `x-data="{ sidebarOpen }"` en el `<body>`-wrapper; en móvil la sidebar es off-canvas. `layouts/navigation.blade.php` fue eliminado. La visibilidad de cada ítem replica EXACTO los `@if($user->esAdministrador())` previos (Empleado: Dashboard/Ventas/Clientes). `<x-layout.nav-item :href :icon :active>`.
- `<x-page title subtitle>` (+ slot `actions`) es el wrapper de contenido: da `max-w-7xl`, padding y `space-y-6`, y renderiza el título grande dentro del área de contenido. Una vista migrada usa `<x-page>` y **deja de pasar** `<x-slot name="header">`. La banda `@isset($header)` del layout sigue para las vistas sin migrar (aún traen su `py-12 max-w-7xl`). `<x-page>` acepta también un slot `heading` (markup libre en vez del prop `title`, p. ej. nombre + código en mono).
Migradas: `dashboard`, `profile/edit`, `auth/*` (`<x-guest-layout>` con slots `heading`/`description`), **`admin/productos/*` + `admin/variantes/edit` + `admin/productos/_form`**, **`admin/inventario/**`**, **`ventas/*`**, **`admin/clientes/*`**, **`admin/creditos/index`**, **`admin/devoluciones/*`**, **`admin/reportes/*`**, **`admin/usuarios/*`**, **`admin/categorias/*`**. → **TODAS las vistas migradas.** Paginación: `resources/views/vendor/pagination/{tailwind,simple-tailwind}.blade.php` re-tematizada con tokens (página activa `primary-600`).
`<x-table>` acepta slots `head` y `foot`.
- Textos de UI: español directo dentro de `__('...')` (no hay archivos de idioma). Al tocar una vista de Breeze, traducir los literales en inglés.
- Componentes base (Etapa D): `<x-button variant=primary|secondary|danger|ghost size=sm|md|lg :href>` (los legacy `<x-primary-button>` etc. son wrappers), `<x-icon-button icon label :href variant>`, `<x-card title subtitle flush>` (+ slot `actions`), `<x-badge variant>`, `<x-alert variant=success|danger|warning|info title>`, `<x-stat label value hint icon tone>`, `<x-table>` (+ slot `head`). Inputs Breeze (`text-input`, `select-input`, `input-label`, `input-error`) ya usan tokens (foco `primary`).
- Flashes de sesión: usar SIEMPRE `<x-alert variant="success">{{ session('status') }}</x-alert>` / `variant="danger"` para `session('error')`. NO volver a escribir `<div class="bg-green-50 ...">`.
- Confirmación de acciones destructivas: NO usar `onsubmit="return confirm(...)"`. Añadir al `<form>` los atributos `data-confirm="pregunta"` (+ `data-confirm-title`, `data-confirm-label`, `data-confirm-variant="danger|primary"`). El `<x-confirm-modal />` (único, en `layouts/app.blade.php`) intercepta el submit, respeta la validación nativa (`required`, etc.: si el form es inválido no abre) y reenvía con `requestSubmit()` tras confirmar.
- Empty states: `<x-empty-state icon title :compact :tone="neutral|positive">` (frase en el slot, CTA en `<x-slot:actions>`). Dentro de una tabla, usar el azúcar `<x-table-empty :colspan icon title tone>` en el `@empty` (envuelve `<tr><td colspan>` + `<x-empty-state compact>`). `tone="positive"` para "no hay nada que atender" (créditos saldados, sin stock bajo). Para listados con filtro, ramificar el `@empty` según `request()` y ofrecer un botón "Ver todas". NO volver a escribir `<tr><td colspan class="py-12 text-center text-ink-faint">`.
- `<x-icon>` aplica `size-5` por defecto solo si quien llama no pasa `size-*`/`h-`/`w-`; pasa el tamaño explícito.
- Sub-navegación por pestañas: `<x-tabs>` + `<x-tabs.link :href :active>` (lo usan `inventario/_nav` y `reportes/_nav`).
- Alpine: NO pongas `x-data` en `<x-table>`/`<table>` — la reactividad no baja a los `<tr x-show>` hijos. Ponlo en un `<div>` que envuelva (p. ej. `<x-card x-data="{...}">`). `x-collapse` NO está disponible (Alpine sin plugins).
- El `<title>` de sección se calcula en `layouts/app.blade.php` desde el nombre de ruta (mapa `$secciones`) → "Productos · JP"; el guest layout usa el slot `heading`. Al añadir un módulo nuevo, agrega su prefijo de ruta al mapa.
- `prefers-reduced-motion` ya está cubierto globalmente en app.css. Duración de transición por defecto = 180ms (fluida).
- Todo el CSS de las vistas usa tokens: NO reintroducir `text-gray-*`, `bg-gray-*`, `indigo-*`, `border-gray-*`, `divide-gray-*`. Componentes `nav-link`/`responsive-nav-link` eliminados (eran de la navbar vieja).
- Tras cambios de assets: `npm run build`. APP_NAME = "JP".
