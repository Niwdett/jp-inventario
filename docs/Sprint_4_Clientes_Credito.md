# Sprint 4 — Devoluciones, saldo a favor, clientes y crédito

**Fase 7 del plan · Duración objetivo: 4 días · Cubre RF-011, RF-012, RF-013, RF-014, RF-015 (y RN-09, RN-11, RN-12, RN-13).**

> **Definición de "hecho":** una devolución genera saldo a favor aplicable; un
> cliente en mora queda bloqueado para crédito nuevo.

---

## 1. Qué se construyó

| Área | Detalle |
|------|---------|
| Gestión de clientes | `/admin/clientes` (RF-013). CRUD solo Administrador; el Empleado solo elige un cliente existente al vender (G1). `nombre` obligatorio; `telefono`/`cedula` opcionales; `cedula` única entre activos (columna generada `activo`). Ficha con saldo a favor, ventas a crédito abiertas y libro de saldo a favor. No se puede eliminar un cliente con crédito pendiente o `saldo_favor > 0`. |
| Saldo a favor | Ledger `saldo_favor_movimientos` + `clientes.saldo_favor` cacheado (C1). Servicio `App\Services\Clientes\MovimientoSaldoFavor` (`generar()` / `aplicar()`), espejo de `MovimientoStock`: el llamador abre la transacción y bloquea la fila del cliente; `aplicar()` valida saldo disponible (`SaldoFavorInsuficienteException`). Nunca se devuelve en efectivo (RN-11); sin vencimiento en el MVP. |
| Venta a crédito | `metodo_pago = credito` activado (RF-008). Genera **una deuda por venta** (C2): `credito_monto`, `credito_saldo_pendiente`. `cliente_id` obligatorio (E1). |
| Saldo a favor como pago | `ventas.saldo_favor_aplicado` (RF-012). Modelo: `total = saldo_favor_aplicado + restante`; el restante se cubre con un `metodo_pago`. Si el saldo cubre el 100 %, la venta se registra como `efectivo` sin deuda. Se puede combinar saldo a favor + crédito (el restante es la deuda). Guarda RN-12: el saldo aplicado nunca supera el total. |
| Control de mora | `Cliente::estaEnMora()` (RN-09): existe una venta a crédito con `credito_saldo_pendiente > 0` y `fecha_venta` anterior a hoy − 15 días (`Cliente::DIAS_MORA`). Antes de confirmar una venta a crédito, dentro de la transacción: el Empleado nunca puede forzarla (`ClienteEnMoraException`); el Administrador sí, con autorización explícita (`autorizar_mora`), que se registra en `credito_autorizado_por`. |
| Abonos | `/admin/creditos` (RF-014). Servicio `App\Services\Creditos\RegistrarAbono`: transacción + `lockForUpdate` sobre la venta + guarda de no sobrepago (`AbonoInvalidoException`). Cada abono baja `credito_saldo_pendiente`; a 0 la deuda queda saldada. Registrado desde la ficha de la venta o el listado de créditos. |
| Devoluciones | `/admin/ventas/{venta}/devoluciones/registrar` (RF-011). Servicio `App\Services\Devoluciones\RegistrarDevolucion`. Solo sobre ventas **confirmadas y entregadas** (si no está entregada, el camino es la anulación). Por línea, el Administrador marca `reintegra_inventario` (RN-13): `true` → `MovimientoStock::reintegrar` + movimiento `tipo=devolucion`; `false` → el stock no cambia. `saldo_generado = Σ(valor_unitario × cantidad)` donde `valor_unitario` es lo que el cliente pagó por unidad (`importe_linea / cantidad`). Estado `validada` (genera saldo a favor) o `rechazada` (se registra para auditoría, sin efecto). |
| Anulación (ampliada) | `AnularVenta` ahora revierte también el dinero del cliente: devuelve el `saldo_favor_aplicado` y convierte los abonos ya registrados en saldo a favor (nunca efectivo, RN-11), y pone `credito_saldo_pendiente = 0`. |

## 2. Decisiones confirmadas antes de implementar (las 7 abiertas)

1. **Saldo a favor + crédito combinables** en la misma venta. Orden: `total → aplicar saldo a favor → restante a crédito`. La mora se comprueba solo si el restante a crédito es > 0. (Cierra el pendiente de `project-status` / Requisitos §12.)
2. **Saldo a favor cubre el 100 %:** la venta se guarda como `metodo_pago = efectivo` con `saldo_favor_aplicado = total` y sin deuda. No se añadió `saldo_favor` al enum.
3. **`valor_unitario` de la devolución** = `venta_lineas.importe_linea / cantidad` (precio real ya con descuento). El Administrador no lo edita en el MVP.
4. **Guardas de la devolución:** solo ventas confirmadas y entregadas; `Σ cantidad devuelta` por `venta_linea` ≤ vendida (varias devoluciones parciales permitidas); la `rechazada` se persiste sin efectos; anular una devolución validada queda fuera de alcance. **Además:** una devolución `validada` exige que la venta tenga cliente (no hay a quién abonar el saldo a favor).
5. **Anulación de venta a crédito con abonos:** `credito_saldo_pendiente = 0` y el total abonado se convierte en saldo a favor. Los abonos quedan registrados.
6. **Eliminar cliente:** bloqueado si tiene alguna venta con `credito_saldo_pendiente > 0` o `saldo_favor > 0` (guarda en el modelo y en el controlador).
7. **Alcance de UI del sprint:** CRUD de clientes + ficha, listado de créditos + abono, formulario de devolución, aviso de mora en la venta con override del Administrador. Los reportes dedicados de crédito/mora van al Sprint 5 con el resto de reportes.

## 3. Tablas nuevas (5 migraciones; ninguna altera columnas existentes)

| Migración | Tabla |
|-----------|-------|
| `create_saldo_favor_movimientos_table` | `saldo_favor_movimientos` (`cliente_id`, `tipo` generado\|aplicado, `monto` con signo, `referencia` polimórfico → devolución o venta, `created_at`) |
| `create_abonos_table` | `abonos` (`venta_id`, `monto`, `fecha`, `usuario_id`, `created_at`) |
| `create_devoluciones_table` | `devoluciones` (`venta_id`, `usuario_id`, `fecha`, `estado` validada\|rechazada, `motivo`, `saldo_generado`, `created_at`) |
| `create_devolucion_lineas_table` | `devolucion_lineas` (`devolucion_id`, `venta_linea_id`, `cantidad`, `reintegra_inventario`, `valor_unitario`) |
| `add_cliente_id_index_to_ventas_table` | índice `(cliente_id, metodo_pago)` en `ventas` |

El esqueleto de `ventas` (columnas de crédito y saldo a favor) ya venía del Sprint 3 (sub-decisión S2): **no hizo falta migración sobre `ventas`**.

## 4. Rutas nuevas

```
# Empleado + Administrador (grupo ventas.*)
—  (sin rutas nuevas; el formulario de venta acepta ahora credito y saldo a favor)

# Solo Administrador (grupo admin.*)
GET   admin/clientes ... admin.clientes.* (resource completo + restaurar)
GET   admin/creditos                       admin.creditos.index
POST  admin/creditos/{venta}/abonos        admin.creditos.abonos.store
GET   admin/devoluciones                   admin.devoluciones.index
GET   admin/ventas/{venta}/devoluciones/registrar  admin.devoluciones.create
POST  admin/ventas/{venta}/devoluciones            admin.devoluciones.store
```

Navegación: enlaces **Clientes**, **Créditos** y **Devoluciones** (solo Administrador).

## 5. Archivos nuevos

- **Migraciones:** las 5 de §3.
- **Modelos:** `SaldoFavorMovimiento`, `Abono`, `Devolucion`, `DevolucionLinea`.
- **Enums:** `TipoSaldoFavor`, `EstadoDevolucion`.
- **Servicios:** `Clientes\MovimientoSaldoFavor`, `Creditos\RegistrarAbono`, `Devoluciones\RegistrarDevolucion`.
- **Excepciones:** `SaldoFavorInsuficienteException`, `ClienteEnMoraException`, `PagoVentaInvalidoException`, `AbonoInvalidoException`, `DevolucionInvalidaException`.
- **Controladores:** `ClienteController`, `CreditoController`, `DevolucionController`.
- **Form Requests:** `StoreClienteRequest`, `UpdateClienteRequest`, `StoreAbonoRequest`, `StoreDevolucionRequest`.
- **Factories:** `SaldoFavorMovimientoFactory`, `AbonoFactory`, `DevolucionFactory`, `DevolucionLineaFactory`.
- **Vistas:** `admin/clientes/{index,create,edit,show,_form}`, `admin/creditos/index`, `admin/devoluciones/{index,create}`.
- **Tests:** `tests/Feature/Sprint4/` (7 archivos) + `tests/Feature/Admin/ClienteManagementTest`.

## 6. Archivos existentes modificados

- `app/Enums/MetodoPago.php` — se elimina `disponiblesEnContado()` (código muerto); docblock actualizado.
- `app/Models/Cliente.php` — `saldoFavorMovimientos()`, `ventasACredito()`, `estaEnMora()`, `DIAS_MORA`, guarda de borrado.
- `app/Models/Venta.php` — `abonos()`, `devoluciones()`, `creditoAutorizadoPor()`, `saldoFavorMovimientos()`, `esCredito()`.
- `app/Models/VentaLinea.php` — `devolucionLineas()`, `cantidadDevuelta()`, `valorUnitarioPagado()`.
- `app/Services/Ventas/RegistrarVenta.php` — parámetros `saldoFavorAplicado` y `autorizarMora`; aplicación de saldo a favor, cálculo del restante, regla de mora, deuda de crédito. Orden de bloqueo: variantes → cliente.
- `app/Services/Ventas/AnularVenta.php` — reversión de saldo a favor aplicado y de abonos, cancelación de la deuda.
- `app/Http/Requests/StoreVentaRequest.php` — acepta `credito`, `saldo_favor_aplicado`, `autorizar_mora`; `cliente_id` obligatorio si crédito o saldo; `saldo_favor_aplicado ≤ total`.
- `app/Http/Controllers/VentaController.php` — pasa los nuevos parámetros; `create()` provee clientes y clientes en mora; `show()` carga crédito/abonos/devoluciones.
- `resources/views/ventas/create.blade.php` — selector de cliente, saldo a favor a aplicar, aviso de mora + casilla de autorización (Alpine).
- `resources/views/ventas/show.blade.php` — panel de crédito, abonos, sección de devoluciones.
- `resources/views/layouts/navigation.blade.php` — enlaces Clientes / Créditos / Devoluciones.
- `resources/css/app.css` — `[x-cloak] { display: none }`.
- `routes/web.php` — rutas de §4.
- `tests/Feature/Ventas/VentaRegistroHttpTest.php` — el test "crédito rechazado en Sprint 3" pasa a "crédito exige cliente (E1)".

## 7. Pruebas (Pest, +58 → 169 en total)

| Archivo | Cubre |
|---------|-------|
| `Sprint4/EsquemaSprint4Test` | tablas, modelos, relaciones y factories (Parte 1) |
| `Sprint4/SaldoFavorTest` | `MovimientoSaldoFavor`: generar suma y registra, aplicar resta y registra en negativo, guarda de saldo insuficiente sin rastro, aplicar el total exacto |
| `Sprint4/ClienteMoraTest` | `estaEnMora()`: sin ventas, deuda < 15 días, deuda > 15 días, deuda saldada |
| `Sprint4/VentaCreditoYSaldoTest` | crédito crea la deuda, exige cliente; saldo a favor parcial; saldo cubre el 100 % → efectivo; combinación saldo + crédito; saldo > disponible / > total; mora bloquea al empleado, admin necesita autorizar, admin autoriza y queda registrado; anulación devuelve abonos y saldo aplicado |
| `Sprint4/VentaCreditoHttpTest` | crédito por HTTP, saldo > total (422), saldo sin cliente (422), empleado no fuerza mora, admin autoriza con la casilla |
| `Sprint4/AbonoTest` | abono parcial, deuda saldada, sobrepago sin rastro, venta que no es a crédito, empleado 403, admin por HTTP, validación de monto/fecha, listado de créditos |
| `Sprint4/DevolucionTest` | validada genera saldo por lo pagado; reintegra solo las líneas marcadas; baja por daño sin tocar stock; rechazada sin efectos; no exceder lo vendido ni acumulando; venta no entregada; venta sin cliente; empleado 403; admin por HTTP; exige al menos una línea |
| `Admin/ClienteManagementTest` | CRUD, empleado 403, cédula única entre activos y reutilizable tras eliminar, cédula opcional, ficha con saldo y créditos, no eliminar con saldo/crédito, eliminar y restaurar |

## 8. Verificación end-to-end (navegador)

Categoría → producto + variante → entrada de mercancía → **venta a crédito** `V-000001` (deuda 160 000) → **abono** de 60 000 (pendiente 100 000) → **marcar entregada** → **devolución validada** de 1 unidad → saldo a favor del cliente **80 000**, stock reintegrado (10 → 8 → 9), 1 `movimiento_inventario` tipo `devolucion`, 1 `saldo_favor_movimiento`. Sin errores de consola.

## 9. Deuda / pendientes para próximos sprints

- **Reportes de crédito / mora / saldo a favor dedicados → Sprint 5** (con RF-017/018/019 y el dashboard).
- **RF-016 Historial de producto → Sprint 5** (Observer sobre `Producto`).
- **`entradas_inventario` sin edición/anulación** (heredado de Sprint 2): un `costo_unitario` mal tecleado sigue contaminando `costo_promedio`. Resolver antes de cargar inventario real.
- **Sin comando de reconciliación stock ↔ ledger** (heredado): valorar `php artisan inventario:verificar` en Sprint 5.
- Una devolución de una venta **de contado** (sin cliente) no puede generar saldo a favor: se rechaza. Si el negocio lo necesita, habría que decidir el destino de ese saldo.
- Anular una devolución ya validada queda fuera de alcance del MVP.
- Sigue pendiente de negocio (Requisitos §12): vencimiento del saldo a favor, plazo formal de crédito (`fecha_vencimiento`), criterio de "uso prolongado" para rechazar devoluciones, tope de descuento del empleado.
