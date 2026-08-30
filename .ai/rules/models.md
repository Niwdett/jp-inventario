---
paths:
  - app/Models/Cliente.php
---

# Models

## Mora: una sola definición (Cliente::enMora / limiteMora)
RN-09: la regla de mora vive en `Cliente` y solo ahí. `Cliente::limiteMora()` = `now()->subDays(self::DIAS_MORA)` (hoy 15 días desde `fecha_venta`, hasta que el negocio defina plazo formal → `fecha_vencimiento`). `#[Scope] enMora()` para consultas (lo usan `DashboardController` y `VentaController@create`); `estaEnMora()` para una instancia (lo usa `RegistrarVenta::aplicarReglaDeMora`). No volver a copiar el `whereHas(...fecha_venta < ...)` en controladores.
