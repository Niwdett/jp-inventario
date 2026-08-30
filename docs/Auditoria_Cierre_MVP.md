# Auditoría de cierre técnico y funcional del MVP — JP Inventario

**Alcance:** RF-001 a RF-020 (Sprints 1–5).
**Rama:** `feat/sprint5-reportes-dashboard` · **HEAD:** `7180d34` (+1 sobre `origin/main`).
**Stack:** Laravel 13.29 · PHP 8.5 · MySQL · Pest 5.
**Fecha:** 2026-08-29.
**Método:** solo lectura. No se modificó código; no hubo commits/push/merge/reset; no se alteraron datos reales. Comprobaciones de lectura + suite de tests (BD de testing aislada) + prueba de humo en el navegador con datos de desarrollo.

---

## A. Resumen ejecutivo

**Veredicto: casi listo.**

El MVP está funcionalmente completo y el núcleo es sólido. Los 20 requisitos están implementados y probados; la lógica crítica (ventas, inventario, crédito, saldo a favor) usa transacciones y bloqueo correctamente, y el historial de costo es inmutable por diseño. La suite (202 tests) está verde y es determinista. Se verificó end-to-end en el navegador: dashboard, reportes, historial y el aislamiento del Empleado respecto a la información financiera.

**No conviene cargar datos reales de inventario todavía.** Hay **un bloqueante duro**: las entradas de mercancía no se pueden editar ni anular, y el costeo es promedio ponderado móvil, así que el primer `costo_unitario` mal tecleado contamina de forma permanente el `costo_promedio` de la variante y la ganancia de todas sus ventas futuras — sin camino de corrección.

Además hay dos asuntos recomendables antes de producción (la acción *marcar entregada* sin transacción, y la ausencia de un comando de reconciliación stock ↔ ledger) y varias decisiones de negocio pendientes que conviene cerrar con JP antes de que existan datos que dependan de ellas (interpretación de RN-05, cómputo de la mora, vencimiento del saldo a favor, tope de descuento del vendedor).

Trabajo estimado para habilitar datos reales con confianza: **corto** — resolver el bloqueante de entradas y hacer `entregar` transaccional. El resto es endurecimiento que puede correr en paralelo.

---

## B. Matriz RF-001 → RF-020

Estados: **VERDE** cumplido · **AMARILLO** parcial / requiere revisión · **ROJO** no cumplido · **GRIS** no verificable.

| RF | Estado | Evidencia | Tests | Riesgos / pendientes |
|----|--------|-----------|-------|----------------------|
| RF-001 | VERDE | Breeze Blade sin registro público; `EnsureRole`; usuario con `deleted_at` no puede autenticar; rate-limit 5 intentos. | Auth/*, RoleAccessTest | — |
| RF-002 | VERDE | CRUD `/admin/usuarios`; enum `Rol`; invariante "≥1 admin activo" (no auto-degradarse / auto-desactivarse). | UsuarioManagementTest | — |
| RF-003 | VERDE | CRUD productos; `codigo_interno` autogenerado con `lockForUpdate` sobre la categoría; categoría inmutable tras el alta. | ProductoManagementTest | — |
| RF-004 | VERDE | Variantes talla/color libre; unicidad `(producto,talla,color)` entre activas vía columna generada; mínimo 1 variante; cascada soft-delete. | VarianteManagementTest | — |
| RF-005 | VERDE | `RegistrarEntrada`: transacción + lock + promedio ponderado móvil (BCMath) + movimiento `entrada`. **Corrección resuelta (A4):** `AnularEntrada` marca la entrada anulada (nunca se borra), reconstruye `costo_promedio`/`stock` reproduciendo el ledger (`ReconstruirCostoVariante`) y anexa un movimiento `anulacion_entrada`; guardas de doble anulación y de stock negativo; `venta_lineas` intacto (RN-05). | EntradaInventarioTest, InventarioLedgerTest, AnularEntradaTest | Solo Admin. Si la entrada mala ya se vendió, exige un ajuste físico previo (guarda A4.b). |
| RF-006 | VERDE | Ruta `rol:administrador` + `StoreAjusteInventarioRequest::authorize()`; fija stock absoluto (RN-10); rechaza delta 0; `usuario_id=NULL` (RN-15). | AjusteInventarioTest | Carrera operativa: venta entre el conteo y el envío del ajuste (inherente al proceso). |
| RF-007 | VERDE | Scope `Variante::stockBajo()` (stock ≤ umbral del producto); página dedicada + badges; excluye productos/variantes eliminados. | AlertaStockTest | — |
| RF-008 | VERDE | `StoreVentaRequest` (precio real, descuento 0–100, método); importes con BCMath; `importe_linea` persistido; un método por venta + saldo a favor aparte. | RegistrarVentaTest, VentaRegistroHttpTest | Sin tope de descuento del Empleado (decisión G4, fuera de MVP). |
| RF-009 | VERDE | `MovimientoStock::descontar()`: `UPDATE … WHERE stock >= :n` + guarda `affected===1` + movimiento `venta`. Invariante stock = último `stock_resultante` verificado en BD. | MovimientoStockTest, InventarioLedgerTest | — |
| RF-010 | VERDE | `AnularVenta`: transacción + relock de la venta + recheck `esAnulable()` + reintegro por línea + movimiento `anulacion`. No permite doble anulación ni anular tras entrega. | AnularVentaTest, VentaAnulacionHttpTest, VentaEntregaTest | Ver E-2: `entregar` no es transaccional; carrera con `anular` deja un registro inconsistente (sin efecto en stock/dinero). |
| RF-011 | VERDE | `RegistrarDevolucion`: solo venta confirmada + entregada + con cliente; `reintegra_inventario` por línea (RN-13); no excede lo vendido acumulando devoluciones. | DevolucionTest | Anular una devolución validada está fuera de alcance del MVP (documentado). |
| RF-012 | VERDE | `MovimientoSaldoFavor::aplicar()` bajo lock del cliente; no excede saldo disponible ni el total; combinación saldo + crédito permitida; si el saldo cubre 100% se guarda `efectivo` sin deuda. | SaldoFavorTest, VentaCreditoYSaldoTest | — |
| RF-013 | VERDE | CRUD `/admin/clientes`; `cedula` única entre activos; no eliminar con crédito pendiente o saldo a favor (guarda en modelo + controlador). | ClienteManagementTest | — |
| RF-014 | VERDE | Una deuda por venta (`credito_monto`, `credito_saldo_pendiente`); `RegistrarAbono` con lock de `ventas` + guarda de no sobrepago; salda al llegar a 0. | AbonoTest | Sin idempotencia: dos abonos por doble clic que juntos no sobrepasen pasarían ambos (ver F-1). |
| RF-015 | VERDE | `Cliente::estaEnMora()` (venta a crédito con saldo > 0 y `fecha_venta` > 15 días); el Empleado no puede forzar; el Admin autoriza y queda en `credito_autorizado_por`. | ClienteMoraTest, VentaCreditoHttpTest | El "atraso" se mide desde `fecha_venta` a falta de plazo formal (pendiente de negocio). Lógica duplicada en 3–4 sitios (ver I-8). |
| RF-016 | VERDE | `ProductoObserver`: fila `alta`; una fila por campo de la whitelist al editar; fila `estado` en `deleted/restored`; `usuario_id` nullable; ruta `withTrashed`. | HistorialProductoTest | Whitelist deliberada: `foto`, `codigo_interno` y `categoria_id` no se auditan. |
| RF-017 | VERDE | `ReportePeriodoRequest` (presets hoy/semana/mes + personalizado); totales, por método y por día; solo ventas confirmadas; devoluciones como dato aparte. | ReporteVentasTest | Tests cubren `personalizado` y `mes`; faltan `hoy` y `semana`. |
| RF-018 | VERDE | `stock × costo_promedio` con BCMath; totales por categoría y generales; oculta agotadas por defecto. Verificado en navegador (valor 595.000,00). | ReporteInventarioTest | Carga toda la colección de variantes en memoria (ver I-10). |
| RF-019 | VERDE | Ganancia al vuelo = `importe_linea − costo_snapshot × cantidad` (RN-04/05); bruta vs neta (devoluciones validadas del periodo); por venta y por producto; comparación con periodo anterior. | ReporteGananciasTest | Cálculos de la comparación viven en `@php` dentro de la Blade (ver I-11). |
| RF-020 | VERDE | `DashboardController`: Admin ve ventas/ganancia/stock bajo/cartera/mora/saldo/top 5; Empleado solo sus ventas de hoy, sin cifras financieras. Verificado: Empleado recibe 403 en reportes y módulos admin. | DashboardTest | El Empleado ve el total (ingreso) de sus propias ventas del día — permitido por diseño (Diseño Funcional). |

**17 VERDE · 1 AMARILLO → resuelto (RF-005 cerrado por la decisión A4, 2026-08-29; RF-015 VERDE con observación) · 0 ROJO · 0 GRIS.**
Ningún requisito se marcó cumplido solo por existir un controlador o una vista: cada VERDE tiene prueba automatizada y, donde aplica, verificación en el navegador.

---

## C. Reglas de negocio (RN-01 → RN-15)

| RN | Estado | Evidencia | Observaciones |
|----|--------|-----------|---------------|
| RN-01 | VERDE | Una columna `variantes.stock`, sin ubicación ni traslados. | — |
| RN-02 | VERDE | `RegistrarVenta` descuenta stock en la misma transacción que confirma la venta. | — |
| RN-03 | VERDE | `venta_lineas.precio_unitario` + `importe_linea` persistidos; independientes de `precio_referencia`. | — |
| RN-04 | VERDE | `VentaLinea::ganancia()` y los 3 reportes: `importe_linea − costo_unitario_snapshot × cantidad`. Permite resultado negativo. | Probado con costo 0 y costo > precio. |
| RN-05 | **AMARILLO** | El `costo_unitario_snapshot` de cada línea es inmutable — las ventas pasadas nunca cambian. | El costeo es **promedio ponderado móvil**: una entrada nueva sí recalcula el `costo_promedio` de las unidades *aún en stock*. La decisión A1 reinterpreta RN-05 como "no modifica `venta_lineas` existentes". Divergencia consciente; conviene confirmarla con el negocio y actualizar el texto de RN-05. |
| RN-06 | VERDE | Rutas `rol:administrador` + `authorize()` en cada Form Request. Verificado: Empleado → 403 en productos, ajustes, clientes, créditos. | — |
| RN-07 | VERDE | Rutas de ventas con `rol:administrador,empleado`; el Empleado ve stock exacto y registra ventas. | — |
| RN-08 | VERDE | `ventas.usuario_id` NOT NULL, fijado por el servicio; `VentaPolicy` limita al Empleado a sus ventas. Verificado: 403 al ver venta ajena. | — |
| RN-09 | VERDE | 15 días (`Cliente::DIAS_MORA`); el Empleado nunca fuerza; el Admin autoriza explícitamente. | "Atraso" medido desde `fecha_venta` (sin `fecha_vencimiento` formal — pendiente de negocio). |
| RN-10 | VERDE | `AjustarInventario` fija el stock al conteo físico + movimiento con el delta. | Nota operativa: si hay ventas entre el conteo y el ajuste, el conteo queda obsoleto. |
| RN-11 | VERDE | La devolución validada solo genera `saldo_favor`; nunca efectivo. Al anular, los abonos se convierten en saldo a favor. | — |
| RN-12 | VERDE | El saldo a favor se aplica como pago; el restante se cubre con un método ("completa la diferencia en dinero"). El saldo no puede exceder el total. | — |
| RN-13 | VERDE | `devolucion_lineas.reintegra_inventario` por línea, decidido por el Admin en la ruta admin de devoluciones. | — |
| RN-14 | VERDE | `productos.umbral_stock_bajo`; el scope de alerta compara contra el umbral del producto de cada variante. | — |
| RN-15 | VERDE | El movimiento de ajuste lleva `usuario_id = NULL`. | El texto dice "usuario y fecha", pero el sistema sí guarda fecha (`created_at`), `motivo` y cantidades. El texto de RN-15 debería decir "no se registra el usuario". |

### Requisitos no funcionales

- **RNF-002 (responsive):** OK — layout Tailwind v4, verificado a 1280px y en el viewport móvil del preview. Tablas anchas con scroll propio.
- **RNF-003 (control de acceso por rol):** OK — middleware + policies + `authorize()` redundante; verificado por ruta directa, no solo por UI.
- **RNF-004 (tiempo real):** OK — el stock se descuenta en la transacción de la venta; los reportes leen en vivo.
- **RNF-005 (concurrencia):** OK con 1 excepción — ver E-2 (`entregar`).
- **RNF-007 (credenciales/datos):** OK — `.env` fuera de git; password con cast `hashed`; CSRF por defecto; sin secretos en el repo.
- **RNF-008 (evolución a V2):** OK — servicios aislados, ledgers, enums; columnas de crédito ya creadas desde Sprint 3. `proveedor` como texto simple deja espacio a RF-021.
- **RNF-009 (usabilidad):** Parcial — flujos claros y mensajes de error en español; sin formato de moneda con símbolo ni separador local, y algunos errores de concurrencia se muestran como texto plano de excepción.

---

## D. Seguridad y autorización

**Fortalezas.** `EnsureRole` compara en modo estricto y aborta 403 sin sesión o sin rol permitido. Cada Form Request repite `authorize()` (defensa en profundidad). `VentaPolicy` no usa `before()` para el Admin, así que las guardas de estado (`esAnulable`, `puedeEntregarse`) también le aplican. IDOR verificado en vivo: un Empleado recibe 403 al abrir la venta de otro y los módulos administrativos. `stock`, `costo_promedio`, totales y campos de crédito no son `fillable` — solo los mueven los servicios. `.env` y variantes fuera de git.

| ID | Severidad | Hallazgo |
|----|-----------|----------|
| D-1 | Bajo | **`costo_unitario_snapshot` e `importe_linea` en `#[Fillable]` de `VentaLinea`.** Hoy no existe ningún flujo que actualice líneas de venta, pero tenerlos en la lista de asignación masiva deja abierta la puerta a mutar la base de la ganancia si se añade un endpoint de edición. Quitarlos es defensa en profundidad sin coste. |
| D-2 | Bajo | **`VentaController@entregar` sin re-autorización bajo lock.** La autorización y el `save()` ocurren fuera de transacción. No es un fallo de permisos, pero sí de integridad — detallado en E-2. |
| D-3 | Bajo | **Sin tope de descuento del Empleado.** El campo *Desc. %* del formulario de venta acepta hasta 100 sin límite por rol. Decisión de negocio pospuesta (G4), no fallo técnico, pero un vendedor puede registrar una venta con 100% de descuento. |
| D-4 | Bajo | **Endurecimiento de despliegue (no verificable aquí).** Confirmar en el hosting: `APP_DEBUG=false`, `APP_ENV=production`, HTTPS forzado, `SESSION_SECURE_COOKIE=true`. La extensión `intl` no está cargada y el log tiene un error antiguo de `Number::format`; no hay uso actual, pero tenerlo presente si se adopta `Number::currency`. |

Sin hallazgos de mass-assignment explotables, sin CSRF deshabilitado, sin exposición de credenciales en el repo, sin queries construidas con concatenación de input. El reset de contraseña usa el flujo estándar de Breeze (aceptable para el MVP).

---

## E. Inventario y concurrencia

Estrategia acordada, verificada línea a línea en `RegistrarVenta`, `AnularVenta`, `RegistrarDevolucion`, `RegistrarEntrada`, `AjustarInventario` y `MovimientoStock`:

| Control | Estado | Detalle |
|---------|--------|---------|
| `DB::transaction` | OK | Envuelve cada operación crítica; el controlador delega sin abrir su propia transacción. |
| `lockForUpdate` sobre variantes | OK | Se bloquean todas las variantes involucradas antes de validar stock. |
| Orden consistente por `id` | OK | Variantes ordenadas por `id`, luego el cliente — mismo orden en venta, anulación y devolución. Sin deadlocks cruzados. |
| Validación de stock | OK | En PHP bajo el lock *y además* en el `UPDATE` condicional. |
| Actualización condicional | OK | `UPDATE variantes SET stock = stock - :n WHERE id = :id AND stock >= :n`. |
| Verificación de filas afectadas | OK | `if ($afectadas !== 1) throw StockInsuficienteException` → rollback total. |
| Rollback ante conflicto | OK | Toda excepción dentro de la clausura revierte la transacción, incluido el incremento de `secuencias`. |
| Registro en `movimientos_inventario` | OK | Un renglón por movimiento con `cantidad` con signo y `stock_resultante`. |
| `stock_resultante` coherente | OK | Invariante `variantes.stock == último stock_resultante` verificado en la BD de desarrollo: **0 discrepancias**. |
| Número de venta correlativo | OK | `Venta::generarNumero()` bloquea la fila `venta` de `secuencias`; verificado en BD: id 4 saltado por un intento fallido, `numero` contiguo (V-000001…V-000004). |

### Escenarios de concurrencia evaluados

- **Vender más de lo disponible / stock negativo** — imposible: columna `unsigned` + `UPDATE` condicional + doble validación.
- **Descontar stock dos veces** — no: cada venta itera sus líneas una sola vez dentro de una única transacción.
- **Reintegrar stock dos veces (anulación)** — no: relock de la venta + recheck `esAnulable()`; la segunda anulación falla.
- **Generar saldo a favor dos veces (devolución)** — no: relock de la venta + `cantidadDevuelta()` recalculado bajo el lock.
- **Gastar el mismo saldo a favor dos veces** — no: lock de la fila del cliente; la validación "saldo disponible ≥ monto" corre en PHP bajo ese lock.
- **Corromper el costo promedio por entradas simultáneas** — no: `RegistrarEntrada` bloquea la fila de la variante.

### Riesgos encontrados

**E-1 · Crítico — Entrada de inventario sin corrección → contaminación permanente del costo. → RESUELTO (decisión A4, 2026-08-29).**
Antes: no había ruta de edición ni de anulación de `entradas_inventario`. Con promedio ponderado móvil, un `costo_unitario` equivocado recalcula `costo_promedio` mezclándolo con el stock existente, y un ajuste posterior solo toca la cantidad, no el costo.
*Solución implementada:* operación **"anular entrada"** (`AnularEntrada` + `ReconstruirCostoVariante`, ruta `PATCH admin/inventario/entradas/{entrada}/anular`, solo Admin). La entrada se marca anulada (nunca se borra), `costo_promedio`/`stock` se **reconstruyen reproduciendo el ledger completo** de la variante, y se anexa un movimiento `anulacion_entrada`. Guardas: doble anulación y stock negativo (exige ajuste físico previo). `venta_lineas` intacto → ganancias históricas inmutables. Ver `Decisiones_Tecnicas_JP.md §A4` y `AnularEntradaTest`.

**E-2 · Medio — `entregar` no es transaccional.**
`VentaController@entregar` hace `$venta->forceFill(['entregada_at' => now()])->save()` sin `DB::transaction`, sin `lockForUpdate` y sin volver a comprobar el estado bajo el lock. En una carrera con `anular`, la anulación puede confirmarse primero y `entregar` escribir después sobre una venta ya anulada → registro con `estado = anulada` *y* `entregada_at` poblado.
*Impacto real bajo:* no afecta stock ni dinero, y las operaciones posteriores (re-anular, devolver) están bloqueadas por sus propios checks de estado. Pero es un registro inconsistente que ensucia la auditoría y contradice la separación "anulación antes / devolución después". `entregar || entregar` también hace doble escritura (inocua).

**E-3 · Bajo — Sin comando de reconciliación stock ↔ ledger.**
El invariante se cumple hoy, pero no hay verificación automatizada ni corrección asistida (pendiente heredado #2). Recomendado antes de operar con datos reales, junto con la reconciliación de `saldo_favor` y `credito_saldo_pendiente` contra sus ledgers.

**E-4 · Bajo — RN-10: conteo obsoleto por venta intercalada.**
El ajuste fija un valor absoluto. Si se vende entre el conteo físico y el envío del formulario, el ajuste sobrescribe el efecto de esa venta. Limitación del proceso, no del código; documentar el procedimiento (contar con el módulo de ventas cerrado, o ajustar por delta).

---

## F. Integridad de datos

**Bien resuelto.** Claves foráneas con `restrictOnDelete` en casi todas las relaciones; `venta_lineas → ventas` y `devolucion_lineas → devoluciones` en cascada (los padres nunca se borran). `producto_historial.producto_id` con `restrictOnDelete` impide el force-delete de un producto con historia. Índices UNIQUE sobre columna generada `activo` (categorías, productos, variantes, clientes) permiten reutilizar nombre/prefijo/cédula tras un soft-delete — correcto y probado. Tipos monetarios: `decimal(12,2)` importes, `decimal(12,4)` costos unitarios; nunca `float` en persistencia ni en cálculo (BCMath en todos los servicios). Los valores cacheados (`saldo_favor`, `credito_saldo_pendiente`) se verificaron consistentes con sus ledgers en la BD de desarrollo.

| ID | Severidad | Hallazgo |
|----|-----------|----------|
| F-1 | Medio | **Abonos sin clave de idempotencia.** `abonos` no tiene restricción que impida dos inserciones equivalentes. La transacción + lock + guarda de sobrepago evita el sobrepago, pero dos abonos pequeños por doble clic que sumados no superen el pendiente se registrarían ambos. Aplica igual al `store` de venta. Mitigable con un token de formulario de un solo uso o una clave de idempotencia. |
| F-2 | Bajo | **`number_format((float) $decimal, 2)` en las vistas.** Las Blade convierten los strings decimales a `float` solo para mostrar. Dentro del rango `decimal(12,2)` el redondeo a 2 decimales es exacto, así que no hay error de cifras, pero rompe visualmente la disciplina "nunca float" y no hay un helper único de moneda. |
| F-3 | Bajo | **Sin CHECK a nivel de BD para saldos no negativos.** `variantes.stock` está cubierto por `unsigned`. `credito_saldo_pendiente`, `saldo_favor` y `saldo_generado` solo se validan en la aplicación. Coherente con el diseño (toda escritura pasa por un servicio), pero un CHECK sería una red de seguridad barata. |
| F-4 | Bajo | **`movimientos_inventario.referencia` polimórfico sin FK.** No hay integridad referencial sobre `referencia_id`. Como ventas, entradas y ajustes nunca se eliminan, no hay huérfanos posibles hoy; a tener en cuenta si en el futuro algo se borra de verdad. |

---

## G. Resultado de la suite de tests

| Métrica | Valor |
|---------|-------|
| Total de tests | 202 |
| Exitosos | 202 |
| Fallos | 0 |
| Errores | 0 |
| Aserciones | 527 |
| Duración | ≈ 22,9 s |
| Runner / BD | Pest 5 · MySQL `jp_inventario_testing` |

Suite verde y determinista (la `VarianteFactory` se estabilizó contra el índice único en Sprint 3). Sin tests marcados como *skipped* o *incomplete*. Cobertura sólida en: autorización por rol en todos los módulos, estados inválidos de venta / devolución / abono, snapshot de costo, aritmética decimal, guarda de no-negatividad, invariante del ledger, mora, saldo parcial y aislamiento entre vendedores.

---

## H. Tests que faltan

Todos pasan, pero hay huecos relevantes. No se agregaron aún; solo se identifican.

### Prioritarios

- **Concurrencia real** (dos transacciones simultáneas con dos conexiones): no existe ninguno. El diseño lo soporta, pero no está demostrado. Prioridad para venta, anulación y aplicación de saldo a favor.
- **Carrera `entregar` vs `anular`** — reproducir el registro inconsistente de E-2.
- **Doble ejecución / idempotencia** — doble `POST` de abono y de venta (F-1).
- **`entregar` por un Empleado sobre la venta de otro** — hay tests para *ver* y *anular* venta ajena, no para *entregar*.

### Complementarios

- Devolver una venta anulada (cubierto por la lógica de estado, sin test explícito).
- RN-04 con ganancia negativa propagada hasta el reporte (existe a nivel de línea).
- Reporte de ventas con presets `hoy` y `semana` (solo hay `personalizado` y `mes`).
- Historial: editar `proveedor` (está en la whitelist del Observer pero sin test propio).
- Anular una venta que aplicó saldo a favor *y* tuvo abonos (las piezas están sueltas en tests distintos).
- Restauración de producto en el borde de la ventana de 10 s (hay "no revive variante borrada aparte", falta el límite temporal).
- Cálculos monetarios en el límite de `decimal(12,2)` / `decimal(12,4)`.

---

## I. Pendientes, clasificados

### ✅ Resueltos

1. ~~**Corrección de entradas de inventario (RF-005 / E-1).**~~ **HECHO (2026-08-29).** Decisión A4 documentada en `Decisiones_Tecnicas_JP.md §A4` e implementada: operación "anular entrada" con reconstrucción del `costo_promedio`/`stock` por reproducción del ledger (`AnularEntrada`, `ReconstruirCostoVariante`), movimiento `anulacion_entrada`, guardas de doble anulación y stock negativo, solo Admin. 11 feature tests (`AnularEntradaTest`). Ya no bloquea la carga de datos reales.

### 🟠 Recomendados antes de producción

2. **Hacer `entregar` transaccional** (`DB::transaction` + `lockForUpdate` + recheck `puedeEntregarse()`). Cierra E-2 y D-2.
3. **Comando de reconciliación** (`stock` vs último `stock_resultante`; `saldo_favor` y `credito_saldo_pendiente` vs sus ledgers). Solo lectura, con `--fix` opcional.
4. **Cerrar las decisiones de negocio** con JP: interpretación de RN-05 (promedio ponderado), cómputo de la mora (RN-09), vencimiento del saldo a favor, tope de descuento del vendedor.
5. **Idempotencia** de abono y de `venta.store`.
6. **Checklist de despliegue** (`APP_DEBUG=false`, HTTPS, cookies seguras, backups de MySQL).
7. **Quitar `costo_unitario_snapshot` e `importe_linea`** del `#[Fillable]` de `VentaLinea` (D-1).
8. **Unificar la lógica de mora** en un solo lugar — hoy está copiada en `Cliente::estaEnMora()`, `DashboardController` y `VentaController@create`.

### 🟡 Mejoras posteriores

9. Helper / componente Blade de moneda que opere sobre strings decimales sin `(float)` (F-2).
10. Reportes y selects que cargan colecciones completas en memoria (`ReporteInventarioController`, `Variante::opcionesParaSelect()`) — mover a agregación en SQL / paginación cuando crezca el catálogo.
11. Sacar los `@php` de cálculo de `reportes/ganancias.blade.php` al controlador.
12. Nomenclatura completa del código interno (pendiente heredado #7).
13. Instalar `intl` si se va a usar `Number::currency`.

### 🟢 V2 — no implementar por iniciativa propia

14. Gestión de proveedores (RF-021), compras con historial (RF-022), productos sin movimiento (RF-023), tope de crédito configurable por cliente (RF-024).
15. Vencimiento formal del saldo a favor, plazo formal de crédito / `fecha_vencimiento`, criterio de "uso prolongado" para devoluciones, anulación de devoluciones validadas.

---

## J. Contradicciones documentación ↔ código

1. **RN-05 (texto) vs decisión A1.** El requisito dice "no actualiza el costo de las unidades ya existentes en inventario"; el promedio ponderado móvil sí combina costo viejo y nuevo para las unidades *aún en stock*. `Decisiones_Tecnicas_JP.md §A1` lo reinterpreta como "no modifica `venta_lineas` existentes". Divergencia consciente y razonada, pero el texto de RN-05 debería actualizarse para reflejar la interpretación aceptada.
2. **RN-15 (texto) vs implementación.** El texto dice "no se registra trazabilidad (usuario y fecha)"; el código sí guarda fecha (`created_at` en `ajustes_inventario` y `movimientos_inventario`) y `motivo`. Solo omite el usuario. El texto debería decir "no se registra el usuario".
3. **Handoff desactualizado.** `memory/project-status.md` dice "Suite Pest en verde (201 tests)"; hoy son 202. Menor.
4. **Sin contradicciones** en: modo oscuro fuera de alcance (no implementado), G3 "marcar entregada" acotado a "propia o admin" (coincide), Sprint 5 sin reporte de cartera separado — los indicadores viven en el dashboard (coincide), modelo de pago "un método por venta + saldo aparte" (consistente entre enum, migración y servicio).

No se modificó ningún documento. Estas correcciones deberían aplicarse junto con el registro de las decisiones de negocio del plan L.

---

## K. Estado de Git

- **Rama actual:** `feat/sprint5-reportes-dashboard` en `7180d34`.
- **Relación con `origin/main`:** 1 commit por delante, 0 por detrás → **merge fast-forward limpio posible**. La rama de Sprint 5 aún es local (no está en `origin`).
- **Commits de los sprints:** presentes y ordenados — Sprint 1 (`16ad380`) → Sprint 5 (`7180d34`), más los commits de documentación de las Fases 2–3.
- **Working tree:** `.ai/rules/index.md` (+1 línea) y `.claude/settings.local.json` (permisos + MCP) modificados. Sin seguir: `.claude/skills/` (5,3 MB — copia vendorizada de *impeccable*), `.claude/agents/`, `.ai/rules/general.md`.
- **Secretos:** ninguno. `.env`, `.env.testing`, `.env.production`, `auth.json`, `*.key` están en `.gitignore` y `git check-ignore` lo confirma. Solo `.env.example` y `.env.testing.example` versionados.
- **Nada que revertir.** No se hicieron commits, push, pull, merge ni reset.

### Recomendaciones menores

- Decidir si `.claude/skills/` y `.claude/agents/` se versionan o se añaden a `.gitignore` (hoy están en limbo).
- Si `.ai/rules/general.md` e `index.md` son reglas del proyecto, commitearlos.
- No hacer push a `main` hasta cerrar los pendientes 🔴 y 🟠 — o bien mergear ahora para congelar el MVP funcional y trabajar el endurecimiento sobre `main` (el código está estable y la suite verde).

---

## L. Plan recomendado (Fase 8 — sin implementar todavía)

1. **Reunión de decisiones con JP.** Cerrar: (a) ¿se acepta el promedio ponderado móvil como método de costeo? (b) ¿la mora se cuenta desde la fecha de venta o hace falta un plazo formal? (c) ¿vence el saldo a favor? (d) ¿tope de descuento del vendedor? Estas respuestas condicionan los pasos 2 y 9.
2. ~~**🔴 Corrección de entradas de inventario.**~~ **HECHO (2026-08-29, rama `feat/a4-anular-entradas`).** Decisión A4 + `AnularEntrada`/`ReconstruirCostoVariante` + 11 tests. La carga de inventario real ya no está bloqueada.
3. **🟠 `entregar` transaccional** + test de la carrera `entregar`/`anular`.
4. **🟠 Comando de reconciliación** stock ↔ ledger y saldos ↔ ledger, con `--fix` opcional.
5. **🟠 Idempotencia** de abono y de `venta.store`.
6. **Tests de concurrencia** (dos conexiones) para venta, anulación y aplicación de saldo a favor.
7. **Limpieza dirigida** — no refactor: unificar la lógica de mora, quitar los `fillable` de más en `VentaLinea`, helper de moneda.
8. **Endurecimiento de despliegue:** checklist de `.env` de producción, HTTPS, cookies seguras, backups de MySQL.
9. **Actualizar documentación:** texto de RN-05 y RN-15, handoff (202 tests) y registro de las decisiones del paso 1.
10. **Merge `feat/sprint5` → `main` + push.** Puede adelantarse si se prefiere congelar el MVP funcional y trabajar el endurecimiento sobre `main`.
11. Luego: **Fase 9** (despliegue) y **Fase 10** (portafolio).

---

*Regla mantenida durante toda la auditoría: no se modificó código, no se hicieron commits/push/merge/reset, no se alteraron datos reales. Las comprobaciones fueron de solo lectura, más la suite de tests (BD de testing aislada) y una prueba de humo en el navegador con datos de desarrollo.*
