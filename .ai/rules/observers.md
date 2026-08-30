---
paths:
  - 'app/Observers/**'
---

# Observers

## ProductoObserver: el soft-delete no dispara `updated`
RF-016 (historial de producto) se registra con `App\Observers\ProductoObserver` vía `#[ObservedBy]` en `Producto`.
Trampa: `SoftDeletes::runSoftDelete()` actualiza la fila con el query builder, NO con `save()`, así que NO dispara el evento `updated`. Registrar el cambio de estado en `deleted()` / `restored()`, no en `updated()`. `restore()` sí llama a `save()` → el Observer ignora ese `updated` con `array_key_exists('deleted_at', $producto->getChanges())`.
Whitelist de campos auditados: nombre, marca, precio_referencia, umbral_stock_bajo, proveedor. `usuario_id = auth()->id()` (nullable: NULL en seeders/consola/tests).
