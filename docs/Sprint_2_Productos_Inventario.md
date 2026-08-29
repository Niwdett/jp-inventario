# Sprint 2 — Productos, variantes, inventario y alertas

**Fase 7 del plan · Duración objetivo: 4 días · Cubre RF-003, RF-004, RF-005, RF-006, RF-007 (y RN-01, RN-04 base, RN-10, RN-14, RN-15).**

> **Definición de "hecho":** CRUD de productos con variantes talla/color, entradas
> de inventario y alerta visual de stock bajo.

---

## 1. Qué se construyó

| Área | Detalle |
|------|---------|
| Categorías | CRUD `/admin/categorias` (A3.2). `prefijo_codigo` en mayúsculas; alimenta el código interno de los productos. Soft-delete + restaurar. **No se elimina una categoría con productos activos** (B2). |
| Productos | CRUD `/admin/productos` (RF-003). `codigo_interno` **autogenerado** `PREFIJO-NNNN`, correlativo por categoría, no editable. `foto` (disco `public`), `umbral_stock_bajo` por producto (RN-14), `precio_referencia` `decimal(12,2)`, `proveedor` texto simple. Soft-delete + restaurar. |
| Variantes | Gestión en la ficha del producto (RF-004). `talla`/`color` texto libre (A3.1), unicidad `(producto_id, talla, color)` entre activas. **Un producto siempre tiene ≥1 variante** (A3): el alta crea la primera; no se puede borrar la última. Al eliminar/restaurar el producto se arrastra el estado a sus variantes. |
| Stock y costo | `variantes.stock` (`unsignedInteger`) y `variantes.costo_promedio` (`decimal(12,4)`) **no son fillable**: solo cambian por entradas y ajustes, cada uno en su transacción. |
| Entradas de mercancía | `/admin/inventario/entradas` (RF-005). Servicio `RegistrarEntrada`: transacción + `lockForUpdate` sobre la variante + recálculo del **costo promedio ponderado móvil** (A1) con aritmética decimal (BCMath, `bcround` a 4 decimales) + renglón en `movimientos_inventario` (`tipo=entrada`, con `usuario_id`). Registro histórico inmutable. |
| Ajustes de inventario | `/admin/inventario/ajustes` (RF-006, RN-10). Servicio `AjustarInventario`: transacción + bloqueo + fija el stock al conteo + `movimientos_inventario` (`tipo=ajuste`, **`usuario_id = NULL`**, RN-15). El `costo_promedio` no cambia. Un ajuste sin cambio de cantidad se rechaza (en el Form Request **y** en el servicio). |
| Libro de movimientos | `/admin/inventario/movimientos` — solo lectura, con filtro por variante (B3b). |
| Alertas de stock bajo | `/admin/inventario/alertas` (RF-007): variantes activas con `stock <= producto.umbral_stock_bajo`. Badge visual "Stock bajo" también en el listado de productos y en la ficha del producto. |
| Navegación | Menús *Productos*, *Categorías*, *Inventario* (solo Administrador). Sub-navegación de Inventario (Entradas / Ajustes / Movimientos / Alertas). |

## 2. Decisiones tomadas en el sprint

- **`codigo_interno` autogenerado secuencial** `PREFIJO-NNNN` (correlativo por categoría, cuenta también los eliminados para no reutilizar números). La "nomenclatura completa" sigue pendiente (Requisitos §12); esto no la bloquea.
- **RF-016 (Historial de producto) NO entra en este sprint**: el plan lo ubica en Sprint 5 con reportes y dashboard. Consecuencia: los cambios de producto de Sprints 2–4 no se auditan hasta que exista el Observer.
- **Lógica de inventario en servicios** (`app/Services/Inventario/`), no en los controladores: la tripleta transacción + bloqueo + ledger se reutiliza en Sprints 3–4 (venta, anulación, devolución).
- **Cascada de soft-delete producto → variantes** vía eventos del modelo `Producto` (`deleting` / `restoring`): una variante de un producto eliminado no debe poder venderse. Al restaurar solo se devuelven las variantes que cayeron con el producto (se identifican por haber sido eliminadas en la misma operación, con un margen de 10 s); una variante borrada antes a mano se queda borrada.

## 2b. Correcciones aplicadas tras la auditoría (antes del commit)

- **Carrera de `codigo_interno`**: el alta ahora bloquea la fila de la categoría (`lockForUpdate`) dentro de la transacción → dos altas simultáneas de la misma categoría se serializan y no calculan el mismo correlativo.
- **Categoría inmutable tras el alta**: `UpdateProductoRequest` ya no acepta `categoria_id` y el formulario de edición la muestra como texto. Cambiar la categoría dejaba el `codigo_interno` inconsistente con el prefijo y abría un segundo camino a la colisión.
- **`Variante`** ahora tiene relaciones `entradas()`, `ajustes()`, `movimientos()` (las necesita Sprint 3 para el reintegro en anulaciones y para verificar el invariante del ledger).
- **`destroy` / `restore` de producto** envueltos en `DB::transaction` (la cascada a variantes es atómica).
- **Guarda "categoría con productos activos"** también a nivel de modelo (`deleting` devuelve `false`), no solo en el controlador.
- **Ajuste sin cambio de cantidad**: el servicio `AjustarInventario` lanza `InvalidArgumentException` (antes solo lo bloqueaba el Form Request).
- **`RegistrarEntrada`** usa `bcround` en vez de truncar el costo promedio a 4 decimales.
- **Código muerto eliminado**: `Producto::conStockBajo()` y `Producto::tieneStockBajo()` (la lógica real vive en `Variante::stockBajo()` + un `withCount` en el controlador).
- **Test nuevo**: `InventarioLedgerTest` verifica el invariante `variantes.stock == último movimiento.stock_resultante` (fundamento de Sprint 3).

## 3. Índices UNIQUE compatibles con soft-delete (bloque F1)

`categorias`, `productos` y `variantes` llevan una columna generada:

```php
$table->unsignedTinyInteger('activo')
    ->storedAs('IF(deleted_at IS NULL, 1, NULL)')
    ->nullable();
```

incluida en los índices únicos (`categorias(nombre, activo)`,
`categorias(prefijo_codigo, activo)`, `productos(codigo_interno, activo)`,
`variantes(producto_id, talla, color, activo)`). Permite reutilizar el código de
un registro eliminado. Los Form Requests validan además
`Rule::unique(...)->whereNull('deleted_at')` para un mensaje amable.

## 4. Migraciones añadidas

| Migración | Tabla |
|-----------|-------|
| `create_categorias_table` | `categorias` |
| `create_productos_table` | `productos` |
| `create_variantes_table` | `variantes` |
| `create_entradas_inventario_table` | `entradas_inventario` |
| `create_movimientos_inventario_table` | `movimientos_inventario` (`referencia` polimórfico, `usuario_id` nullable) |
| `create_ajustes_inventario_table` | `ajustes_inventario` (sin `usuario_id`, RN-15) |

## 5. Rutas nuevas (todas `auth` + `rol:administrador`)

```
resource  admin/categorias                 (sin show) + PATCH .../restaurar
resource  admin/productos                              + PATCH .../restaurar
resource  admin/productos/{producto}/variantes  (store, edit, update, destroy)
GET/POST  admin/inventario/entradas         index · registrar · store
GET/POST  admin/inventario/ajustes          index · registrar · store
GET       admin/inventario/movimientos      index (filtro ?variante_id=)
GET       admin/inventario/alertas          index
```

## 6. Prerrequisito resuelto — tests sobre MySQL

Los feature tests corrían en SQLite en memoria, incompatible con la columna
generada `activo`. Ahora:

- `.env.testing` → BD `jp_inventario_testing` (misma instancia MySQL80).
  **No versionado**; plantilla en `.env.testing.example`.
- `phpunit.xml` ya no fuerza `DB_CONNECTION=sqlite`.
- `RefreshDatabase` sobre MySQL.

```bash
# crear la BD de pruebas una sola vez
mysql -u root -p -e "CREATE DATABASE jp_inventario_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cp .env.testing.example .env.testing   # y ajustar credenciales
```

## 7. Cómo levantarlo desde cero

```bash
php artisan migrate:fresh --seed   # admin@jp.test / vendedor@jp.test, clave: password
php artisan storage:link
npm run build
php artisan serve
```

## 8. Pruebas (Pest, +40 → 73 en total)

| Archivo | Cubre |
|---------|-------|
| `tests/Feature/Admin/CategoriaManagementTest.php` | CRUD, validación, unicidad entre activas, reutilizar prefijo de eliminada, no borrar con productos, restaurar |
| `tests/Feature/Admin/ProductoManagementTest.php` | rol, alta con primera variante, código autogenerado + correlativo, validación, redirección sin categorías, foto, código y categoría inmutables al editar, badge de stock bajo en el listado, cascada de soft-delete, restaurar no revive variantes borradas aparte |
| `tests/Feature/Admin/VarianteManagementTest.php` | agregar, unicidad talla/color, editar, no borrar la última, borrar con más de una, aislamiento entre productos |
| `tests/Feature/Admin/EntradaInventarioTest.php` | rol, stock + costo promedio ponderado, primera entrada fija el costo, movimiento con usuario, validación |
| `tests/Feature/Admin/AjusteInventarioTest.php` | rol, ajuste ↓/↑, movimiento con delta y **sin usuario** (RN-15), costo intacto, no ajustar a la misma cantidad (Form Request y servicio) |
| `tests/Feature/Admin/AlertaStockTest.php` | rol, filtro por umbral, ignora productos eliminados, acceso al libro de movimientos |
| `tests/Feature/Admin/InventarioLedgerTest.php` | invariante `variantes.stock == último movimiento.stock_resultante`; `stock_resultante` correcto en secuencia |

## 9. Deuda / pendientes para próximos sprints

- **RF-016 Historial de producto** → Sprint 5 (Observer sobre `Producto`).
- **`entradas_inventario` sin edición/anulación**: una entrada con un `costo_unitario` mal tecleado contamina `costo_promedio` y un ajuste solo corrige la *cantidad*, no el *costo*. **Resolver antes de cargar inventario real** (entrada de reversa o edición con recálculo). No bloquea Sprint 3.
- **Descuento de stock con guarda de no-negatividad** (bloque B1): Sprint 3 debe usar un `UPDATE ... WHERE stock >= :n` condicional y verificar filas afectadas, **no** el `save()` plano que usan las entradas.
- **Sin comando de reconciliación stock ↔ ledger**: hoy solo lo cubre `InventarioLedgerTest`. Valorar un `php artisan inventario:verificar` en Sprint 5.
- La extensión **GD** no está instalada en el PHP local: los tests de imagen usan `UploadedFile::fake()->create(...)` con mime explícito en vez de `->image()`. En producción no hace falta GD (la foto se guarda tal cual, sin redimensionar).
