---
paths:
  - 'app/Policies/**'
---

# Policies

## Permisos de Empleado sobre clientes y abonos (G1-bis)
Decisión G1-bis (2026-08-29, negocio): el Empleado puede crear/consultar/EDITAR clientes (`ClientePolicy`: viewAny/view/create/update → true; delete/restore → solo Admin) y registrar abonos SOLO de las ventas a crédito que él mismo registró (`VentaPolicy::abonar()` = esCredito + confirmada + saldo pendiente + esPropiaOEsAdmin). La cartera agregada `creditos.index` sigue siendo solo Admin. Rutas: `clientes` resource + `clientes.restore` + `creditos/{venta}/abonos` viven en un grupo `rol:administrador,empleado` con prefix('admin')->name('admin.') (nombres `admin.clientes.*` sin cambio para no romper vistas). `ClienteController` autoriza inline por método (`$this->authorize(...)`) — NO usa `authorizeResource()` en el constructor: en Laravel 12 el Controller base no tiene `middleware()` y peta. Docs: `docs/Diseno_Funcional_JP.md §3 G1-bis`, `CLAUDE.md` ROLES.
