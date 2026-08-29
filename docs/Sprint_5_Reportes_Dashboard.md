# Sprint 5 — Historial de producto, reportes y dashboard

**Fase 7 del plan · Duración objetivo: 2 días · Cubre RF-016, RF-017, RF-018, RF-019, RF-020 (y RN-04, RN-05).**

> **Definición de "hecho":** el dashboard refleja ventas, inventario y ganancias
> reales del sistema.

Con este sprint el MVP cubre RF-001 a RF-020 de punta a punta.

---

## 1. Qué se construyó

| Área | Detalle |
|------|---------|
| Historial de producto (RF-016) | Tabla `producto_historial` + `App\Observers\ProductoObserver` sobre el modelo `Producto` (`#[ObservedBy]`). Una fila `campo = 'alta'` al crear; una fila por campo modificado al editar (`nombre`, `marca`, `precio_referencia`, `umbral_stock_bajo`, `proveedor`); una fila `campo = 'estado'` al desactivar / reactivar. `usuario_id` sale de la sesión (NULL fuera de una petición). Solo lectura, en `GET admin/productos/{producto}/historial` (acepta productos eliminados). Sin paquetes externos (B3a). |
| Reporte de ventas por periodo (RF-017) | `GET admin/reportes/ventas`. Presets día / semana / mes + rango personalizado (`App\Http\Requests\ReportePeriodoRequest`). Totales del periodo (nº, subtotal, descuentos, total, saldo a favor aplicado), desglose por método de pago y por día. Solo ventas **confirmadas**. Las devoluciones validadas del periodo se muestran como dato informativo aparte. |
| Reporte de inventario disponible (RF-018) | `GET admin/reportes/inventario`. Foto del stock actual: por variante activa, unidades, costo promedio y **valor = `stock × costo_promedio`** (BCMath), con totales por categoría y generales. Oculta variantes agotadas salvo `?incluir_agotadas=1`. Sin periodo (criterio de aceptación §14). |
| Reporte de ganancias (RF-019) | `GET admin/reportes/ganancias`. `ganancia_linea = importe_linea − costo_unitario_snapshot × cantidad` (RN-04); el snapshot es inmutable (RN-05). **Bruta** = ganancias de las ventas del periodo. **Neta** = bruta − efecto de las devoluciones validadas del periodo. Vistas: resumen, por venta (bruta), por producto (neta, con margen %), y comparación opcional con el periodo inmediato anterior de la misma duración. |
| Dashboard (RF-020) | `App\Http\Controllers\DashboardController`. **Administrador:** ventas de hoy y del mes, ganancia bruta del mes, variantes en stock bajo, crédito por cobrar, clientes en mora, saldo a favor de clientes, top 5 productos del mes. Cada tarjeta enlaza a su módulo. **Empleado:** solo sus ventas de hoy y accesos rápidos (sin información financiera, criterio de aceptación §14). |

## 2. Decisiones tomadas antes de implementar

El usuario delegó la resolución de las decisiones abiertas. Se resolvieron así:

1. **Historial de producto — alcance de campos.** Se auditan solo los campos de negocio editables: `nombre`, `marca`, `precio_referencia`, `umbral_stock_bajo`, `proveedor`. Se excluyen `codigo_interno` y `categoria_id` (inmutables tras el alta) y `foto` (es un archivo; su ruta interna no aporta a una auditoría de negocio). El alta se registra como **una** fila `campo = 'alta'`, no una por campo inicial.
2. **Historial — desactivar / reactivar sí se registra** (`campo = 'estado'`): es información útil y barata. El soft-delete no pasa por el evento `updated` de Eloquent (SoftDeletes actualiza la fila sin `save()`), así que lo cubren los métodos `deleted()` / `restored()` del Observer.
3. **Historial — las variantes quedan fuera** del historial de producto en el MVP: su alta/baja es visible en la ficha y su stock tiene su propio libro (`movimientos_inventario`).
4. **Ventas por periodo y devoluciones se reportan por separado.** Una venta ocurrió; la devolución es otro hecho, con su propia fecha. RF-017 no descuenta devoluciones; RF-019 sí calcula la ganancia neta.
5. **Efecto de las devoluciones en la ganancia (RF-019).** Por cada línea de una devolución **validada** cuya `fecha` cae en el periodo:
   - se revierte el ingreso abonado como saldo a favor: `valor_unitario × cantidad`;
   - si la línea reintegró inventario (`reintegra_inventario = true`), se recupera su costo: `costo_unitario_snapshot × cantidad`;
   - `ganancia_revertida = ingreso_revertido − costo_recuperado`.

   Con reintegro el efecto neto sobre la utilidad de esa unidad es 0; sin reintegro (producto dañado, RN-13) la pérdida es el costo de la unidad. Las devoluciones `rechazada` no tienen efecto.
6. **No hay reporte de cartera / mora separado.** El árbol de Reportes del diseño funcional solo lista ventas, inventario y ganancias; crédito y mora ya tienen sus pantallas (Sprint 4). Los indicadores de cartera (crédito por cobrar, clientes en mora, saldo a favor) viven en el **dashboard**.
7. **Dashboard — "ganancia bruta del mes"** (no neta) en la tarjeta, para mantener la consulta trivial; el detalle neto está a un clic en el reporte de Ganancias. El Empleado no ve ninguna cifra financiera.
8. **Comparación de periodos (RF-019):** el periodo anterior es la ventana inmediata anterior **de la misma cantidad de días** (p. ej. "este mes" de 31 días → los 31 días anteriores al día 1). Se compara ingreso y ganancia neta.

## 3. Tablas nuevas (1 migración; no altera columnas existentes)

| Migración | Tabla |
|-----------|-------|
| `create_producto_historial_table` | `producto_historial` (`producto_id`, `usuario_id` nullable, `campo`, `valor_anterior`, `valor_nuevo`, `created_at`). Índice `(producto_id, id)`. Sin `updated_at`. |

Los reportes y el dashboard **no necesitaron tablas ni columnas nuevas**: leen de `ventas`, `venta_lineas`, `devoluciones`, `devolucion_lineas`, `variantes`, `productos` y `clientes`. La ganancia se calcula al vuelo desde el snapshot inmutable de `venta_lineas` (no se persiste una columna `ganancia`).

## 4. Rutas nuevas

```
# Solo Administrador (grupo admin.*)
GET  admin/productos/{producto}/historial   admin.productos.historial   (withTrashed)
GET  admin/reportes/ventas                   admin.reportes.ventas
GET  admin/reportes/inventario               admin.reportes.inventario
GET  admin/reportes/ganancias                admin.reportes.ganancias

# Todos los roles
GET  /dashboard   ahora → DashboardController@index (antes era una clausura)
```

Navegación: enlace **Reportes** (solo Administrador). Botón **Historial** en la ficha del producto.

## 5. Archivos principales

- **Historial:** `app/Models/ProductoHistorial.php`, `app/Observers/ProductoObserver.php`, `app/Http/Controllers/ProductoHistorialController.php`, `resources/views/admin/productos/historial.blade.php`, `database/factories/ProductoHistorialFactory.php`. `Producto` gana `#[ObservedBy]` y la relación `historial()`.
- **Reportes:** `app/Http/Requests/ReportePeriodoRequest.php` (resuelve el periodo y el periodo anterior; compartido por ventas y ganancias), `app/Http/Controllers/Reporte{Ventas,Inventario,Ganancias}Controller.php`, `resources/views/admin/reportes/{_nav,_filtros,ventas,inventario,ganancias}.blade.php`, `resources/views/components/reportes/tarjeta.blade.php`.
- **Dashboard:** `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard.blade.php`.
- **Modelo `Venta`:** scope `confirmadas()` y helper `gananciaBruta()`. `VentaLinea::ganancia()` ya existía desde el Sprint 3.

## 6. Pruebas (feature, MySQL)

`tests/Feature/Sprint5/`: `HistorialProductoTest` (10), `ReporteVentasTest` (6), `ReporteInventarioTest` (4), `ReporteGananciasTest` (10), `DashboardTest` (5). Cubren: rol (Empleado 403 en todo lo administrativo), captura de usuario en el historial, exclusión de ventas anuladas y fuera de rango, agregación por método / día / categoría / producto, uso del snapshot de costo, efecto de devoluciones con y sin reintegro, comparación de periodos y su ventana, y el corte de información financiera para el Empleado en el dashboard.

Suite completa: **201 pruebas en verde** (169 heredadas + 32 nuevas).

## 7. Verificación en navegador

Flujo real ejercitado: alta de categoría → alta de producto (genera `alta` en el historial) → edición de nombre/precio/umbral (3 filas más, atribuidas al Administrador) → entrada de mercancía (20 × 45 000) → venta de contado (4 × 85 000 − 10 %). Resultado:

- **Dashboard:** ventas de hoy 1 (306 000), ganancia bruta del mes 126 000, top producto Camisa Oxford.
- **Ganancias:** ingreso 306 000, bruta 126 000, neta 126 000, margen 41,1 %; comparación con `2026-07-01 — 2026-07-31`.
- **Inventario:** 16 unidades, valor 720 000.
- **Ventas por periodo:** 1 venta, subtotal 340 000, descuentos 34 000, total 306 000.

## 8. Pendientes heredados (no abordados aquí)

- **Entradas de inventario sin editar/anular:** un costo mal tecleado contamina `costo_promedio`. Resolver **antes** de cargar inventario real.
- **Comando de reconciliación stock ↔ ledger.** Sigue como candidato.
- Pendientes de negocio (Requisitos §12): vencimiento del saldo a favor, plazo formal de crédito / `fecha_vencimiento`, tope de descuento del empleado, criterio de "uso prolongado" para devoluciones, nomenclatura completa del código interno.
