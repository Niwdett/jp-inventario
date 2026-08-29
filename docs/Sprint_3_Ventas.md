# Sprint 3 — Ventas y anulación

**Fase 7 del plan · Duración objetivo: 4 días · Cubre RF-008, RF-009, RF-010 (y RN-03, RN-04 base, RN-08, RNF-004, RNF-005).**

> **Definición de "hecho":** se registra una venta completa, descuenta stock
> automáticamente y puede anularse antes de la entrega.

---

## 1. Qué se construyó

| Área | Detalle |
|------|---------|
| Registrar venta | `/ventas/registrar` (RF-008). Formulario con líneas dinámicas (Alpine): variante, cantidad, precio real (RN-03), descuento % opcional por línea. Método de pago `efectivo` o `transferencia`. Sin cliente obligatorio. |
| Confirmación / descuento de stock | Servicio `App\Services\Ventas\RegistrarVenta` (RF-009). Transacción + `lockForUpdate` sobre las variantes ordenadas por `id` + validación previa `stock >= cantidad` + descuento con **`UPDATE ... WHERE stock >= :n`** condicional (guarda de no-negatividad, bloque B1) + movimiento `tipo=venta`. |
| Snapshot de costo | `venta_lineas.costo_unitario_snapshot` toma `variantes.costo_promedio` vigente bajo lock (A2). Inmutable: cambios posteriores de costo no tocan ventas pasadas (RN-05). Una variante con `costo_promedio = 0` se puede vender y guarda snapshot 0 (decisión aprobada del sprint). |
| Importes | `importe_linea = round(precio · cantidad · (1 − desc%/100), 2)` con BCMath (E2). `ventas.subtotal` = Σ(precio·cantidad), `ventas.total` = Σ(importe_linea), `ventas.descuento_total` = subtotal − total. Nunca `float`. |
| Número de venta | `V-000001`, `V-000002`, … Correlativo global, único permanente, sin reinicio anual, sin reutilización. Se genera con `Venta::generarNumero()` bloqueando (`lockForUpdate`) la fila `venta` de la tabla `secuencias` dentro de la transacción (sub-decisión S1). |
| Anulación | `/ventas/{venta}/anular` (RF-010). Servicio `App\Services\Ventas\AnularVenta`. Transacción + recarga con lock + revalidación `esAnulable()` + lock de variantes + reintegro de stock + movimiento `tipo=anulacion` + `estado=anulada`, `anulada_at`, `anulada_por`, `motivo_anulacion` (motivo obligatorio). Solo si `entregada_at IS NULL`. |
| Marcar entregada | `/ventas/{venta}/entregar` (decisión G3). Fija `entregada_at`. Tras la entrega la venta ya no se puede anular (el camino sería la devolución, Sprint 4). |
| Listado / detalle | `/ventas` — el Empleado ve **solo sus** ventas (RN-08, "Mis ventas"); el Administrador, todas. Filtro por estado. `/ventas/{venta}` — detalle con líneas, totales y acciones. |
| Autorización | `App\Policies\VentaPolicy`. Empleado y Administrador registran y consultan; el Empleado solo opera sus ventas; anular exige que la venta siga siendo anulable **también** para el Administrador. Ruta con `rol:administrador,empleado`. |
| Navegación | Enlace **Ventas** visible para ambos roles (fuera del bloque solo-Administrador). |

## 2. Decisiones tomadas en el sprint (confirmadas antes de implementar)

- **S1 — Número de venta:** tabla `secuencias` (`nombre` PK, `valor`), fila `venta` sembrada en la propia migración. `Venta::generarNumero()` bloquea e incrementa dentro de la transacción de la venta. Un `ROLLBACK` revierte el incremento → sin huecos y sin reutilización. Alternativa descartada: `V-` + `id` (filtra la PK, deja huecos).
- **S2 — Esquema para Sprint 4 creado ahora, sin lógica:** la tabla `ventas` lleva ya todas las columnas del bloque E (`cliente_id` nullable, `saldo_favor_aplicado`, `credito_monto`, `credito_saldo_pendiente`, `credito_autorizado_por`). Se crea la tabla `clientes` **inerte** (modelo sin lógica, sin rutas, sin menú) solo para que la FK `ventas.cliente_id` sea válida. `abonos`, `saldo_favor_movimientos`, `devoluciones` y `devolucion_lineas` **no** se crean: no tienen acoplamiento estructural con `ventas` y son 100 % Sprint 4.
- **S3 — Marcar entregada entra en Sprint 3:** es parte del ciclo de vida de la venta y sin ella no se puede probar la guarda "ya entregada → no anulable".
- **S4 — `metodo_pago` enum incluye `credito`** en la BD (para Sprint 4), pero `StoreVentaRequest` solo acepta `efectivo` y `transferencia`.
- **Costo 0 no bloquea la venta:** se registra el snapshot con el valor vigente. El aviso visual queda como mejora futura.
- **`entregar` acotado a "propia o Administrador":** coherente con `view` (un Empleado no opera lo que no puede ver). Si el negocio quiere que cualquier vendedor marque la entrega, es un cambio de una línea en la Policy.

## 3. Lógica de stock reutilizable (bloque B1)

`App\Services\Inventario\MovimientoStock` — helper con `descontar()` y `reintegrar()`. **Contrato:** el llamador ya abrió la transacción y ya bloqueó la fila de la variante. `descontar()` hace el `UPDATE` condicional, exige `affected === 1` (si no → `StockInsuficienteException`, que revierte todo) y anexa el movimiento; `reintegrar()` suma (sin guarda) y anexa. Lo usan `RegistrarVenta` y `AnularVenta`, y lo usará la devolución (Sprint 4).

`RegistrarEntrada` y `AjustarInventario` **no cambian**: sus semánticas (recalcular costo promedio, fijar stock absoluto) son distintas de descontar/reintegrar.

## 4. Migraciones añadidas

| Migración | Tabla |
|-----------|-------|
| `create_secuencias_table` | `secuencias` (`nombre` PK, `valor`; fila `venta` sembrada en la migración) |
| `create_clientes_table` | `clientes` (inerte; `saldo_favor` cacheado default 0; columna generada `activo` + `unique(cedula, activo)`) |
| `create_ventas_table` | `ventas` (sin `softDeletes` — B2; enum `metodo_pago` incluye `credito`; enum `estado` confirmada/anulada; columnas de crédito nullable para Sprint 4) |
| `create_venta_lineas_table` | `venta_lineas` (`costo_unitario_snapshot` `decimal(12,4)`, `importe_linea` `decimal(12,2)` persistido) |

## 5. Rutas nuevas (`auth` + `rol:administrador,empleado`)

```
GET   ventas                     ventas.index    (Empleado: solo propias)
GET   ventas/registrar           ventas.create
POST  ventas                     ventas.store
GET   ventas/{venta}             ventas.show
PATCH ventas/{venta}/anular      ventas.anular
PATCH ventas/{venta}/entregar    ventas.entregar
```

## 6. Archivos nuevos

- **Migraciones:** las 4 de §4.
- **Modelos:** `App\Models\Venta`, `App\Models\VentaLinea`, `App\Models\Cliente`.
- **Enums:** `App\Enums\MetodoPago`, `App\Enums\EstadoVenta`.
- **Servicios:** `App\Services\Inventario\MovimientoStock`, `App\Services\Ventas\RegistrarVenta`, `App\Services\Ventas\AnularVenta`.
- **Excepciones:** `App\Exceptions\StockInsuficienteException`, `App\Exceptions\VentaNoAnulableException`.
- **Policy:** `App\Policies\VentaPolicy` (auto-descubierta).
- **Form Requests:** `App\Http\Requests\StoreVentaRequest`, `App\Http\Requests\AnularVentaRequest`.
- **Controlador:** `App\Http\Controllers\VentaController`.
- **Factories:** `VentaFactory`, `VentaLineaFactory`, `ClienteFactory`.
- **Vistas:** `resources/views/ventas/{index,create,show}.blade.php`.
- **Tests:** `tests/Feature/Ventas/` (7 archivos, ver §8).

## 7. Archivos existentes modificados

- `app/Http/Controllers/Controller.php` — `use AuthorizesRequests`.
- `app/Models/Variante.php` — relación `ventaLineas()`.
- `routes/web.php` — grupo `ventas.*`.
- `resources/views/layouts/navigation.blade.php` — enlace *Ventas* para ambos roles.

## 8. Pruebas (Pest, +36 → 109 en total)

| Archivo | Cubre |
|---------|-------|
| `RegistrarVentaTest` | descuento de stock, movimiento `venta`, snapshot de costo (y su inmutabilidad), costo 0, correlativo `V-000001`, cálculo de subtotal/descuento/total con BCMath, rechazo por stock insuficiente con rollback total, no reutilización del número tras fallo |
| `AnularVentaTest` | reintegro de stock + movimiento `anulacion`, auditoría (`anulada_por`/`_at`/`motivo`), no anular entregada, no anular dos veces, invariante `stock == último movimiento` tras venta+anulación |
| `MovimientoStockTest` | `UPDATE` condicional, `StockInsuficienteException` sin efectos colaterales, guarda de no-negatividad en segundo descuento, `reintegrar` sin guarda |
| `VentaRegistroHttpTest` | invitado→login, Empleado y Administrador registran, `credito` rechazado (S4), validación de líneas, variante repetida, stock insuficiente vuelve con error, descuento por línea |
| `VentaAnulacionHttpTest` | Empleado anula su venta, Administrador anula cualquiera, Empleado 403 sobre venta ajena, no anular entregada (403), motivo obligatorio |
| `VentaEntregaTest` | Empleado marca entregada, no re-entregar, tras entrega `anular` da 403 |
| `VentaAutorizacionTest` | listado del Empleado solo sus ventas, Administrador ve todas, `show` ajeno 403 para Empleado / OK para Administrador, invitado→login |

## 9. Deuda / pendientes para próximos sprints

- **Crédito, abonos, mora, saldo a favor, gestión de clientes, devoluciones → Sprint 4.** El esquema de `ventas` ya los soporta; falta la lógica, el CRUD de clientes y las tablas `abonos` / `saldo_favor_movimientos` / `devoluciones` / `devolucion_lineas`.
- **`entradas_inventario` sin edición/anulación** (heredado de Sprint 2): un `costo_unitario` mal tecleado sigue contaminando `costo_promedio`. Resolver antes de cargar inventario real.
- **`VarianteFactory` con colisiones aleatorias**: `talla`/`color` salen de un rango pequeño y a veces chocan con el índice único, haciendo fallar `ProductoManagementTest` de forma intermitente. Pendiente de arreglar en la factory (no afecta a producción).
- **Sin comando de reconciliación stock ↔ ledger** (heredado): valorar `php artisan inventario:verificar` en Sprint 5.
- **RF-016 Historial de producto → Sprint 5** (Observer sobre `Producto`).
- La política de `entregar` asume "propia o Administrador"; confirmar con el negocio si cualquier vendedor debe poder marcar la entrega.
