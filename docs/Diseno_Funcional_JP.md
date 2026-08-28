# Diseño Funcional — Sistema JP (Fase 2)

**Versión 1.0** — Agosto de 2026

> Entregable de la Fase 2 del plan de proyecto: árbol de módulos por rol y diagramas de los
> flujos principales del negocio. Traduce los requisitos (`Documento_Requisitos_Software_JP.md`)
> y las decisiones técnicas (`Decisiones_Tecnicas_JP.md`) en pantallas y procesos concretos,
> sin entrar todavía en el detalle visual.

---

## 1. Árbol de módulos — Administrador

```
Administrador
├── Dashboard                         RF-020
│
├── Productos
│   ├── Categorías                    (CRUD; no eliminar con productos activos)
│   ├── Productos                     RF-003
│   │   ├── Crear / Editar / Listar
│   │   ├── Variantes del producto    RF-004  (talla, color, stock, costo)
│   │   └── Historial del producto    RF-016  (solo lectura)
│   └── Inventario
│       ├── Entradas de mercancía     RF-005  (recalcula costo promedio)
│       ├── Ajustes de inventario     RF-006  (RN-10, RN-15)
│       ├── Movimientos               (libro movimientos_inventario, solo lectura)
│       └── Alertas de stock bajo     RF-007  (umbral por producto, RN-14)
│
├── Ventas
│   ├── Registrar venta               RF-008, RF-009, RF-012
│   ├── Listado / Detalle de ventas
│   ├── Anulación (antes de entrega)  RF-010  (reintegra stock)
│   └── Devoluciones (tras entrega)   RF-011  (valida y genera saldo a favor)
│
├── Clientes
│   ├── Clientes                      RF-013
│   ├── Créditos                      RF-014  (deuda por venta, saldo pendiente)
│   ├── Abonos                        RF-014  (parciales, fechados)
│   ├── Control de mora               RF-015  (RN-09, override del Administrador)
│   └── Saldo a favor                 (libro saldo_favor_movimientos, solo lectura)
│
├── Reportes
│   ├── Ventas por periodo            RF-017  (día / semana / mes)
│   ├── Inventario disponible         RF-018
│   └── Ganancias                     RF-019  (por venta y por producto, comparación de periodos)
│
└── Usuarios                          RF-002  (crear, editar, asignar rol)
```

## 2. Árbol de módulos — Empleado / Vendedor

```
Empleado / Vendedor
└── Ventas
    ├── Buscar producto / consultar stock   RN-07  (cantidad exacta por variante)
    ├── Registrar venta                     RF-008, RF-009
    │   ├── Seleccionar variantes y cantidades
    │   ├── Capturar precio real (RN-03) y descuento opcional
    │   ├── Seleccionar método de pago
    │   ├── Aplicar saldo a favor            RF-012  (si hay cliente)   [ver decisión G1]
    │   └── Confirmar (descuenta stock)      RF-009
    ├── Anular venta propia (antes de entrega)  RF-010  [ver decisión G2]
    └── Mis ventas                          (solo las registradas por este usuario, RN-08)
```

**El Empleado NO ve:** Dashboard, Reportes, Productos, Categorías, Inventario, gestión de
Clientes, Créditos, Usuarios. Middleware `EnsureRole` bloquea el acceso (RNF-003).

---

## 3. Decisiones funcionales abiertas (requieren confirmación)

Los requisitos no son explícitos en estos puntos. Recomendación entre corchetes.

| # | Pregunta | Recomendación |
|---|----------|---------------|
| **G1** | ¿Puede el Empleado registrar ventas a **crédito** / usar **saldo a favor** / elegir un **cliente**? | El Empleado **puede seleccionar un cliente existente** y registrar venta a crédito (sujeta al bloqueo por mora, que él no puede saltar) y aplicar saldo a favor. **No puede crear ni editar clientes** — eso queda en el módulo Clientes del Administrador. |
| **G2** | ¿Puede el Empleado **anular** una venta? | Sí, pero **solo ventas registradas por él y aún no entregadas**. El Administrador puede anular cualquiera. |
| **G3** | ¿Puede el Empleado marcar una venta como **entregada**? | Sí. La entrega es una acción operativa del punto de venta, no administrativa. |
| **G4** | ¿Existe un **descuento máximo** que el Empleado puede aplicar sin autorización? | Pendiente de negocio (Requisitos §12). **MVP: sin límite**, se registra el precio real tal cual. Si el negocio define un tope, se añade una validación. |

---

## 4. Flujos principales

### 4.1 Alta de producto hasta que es vendible

```mermaid
flowchart TD
    A[Administrador crea Categoría<br/>si no existe] --> B[Crea Producto<br/>nombre, marca, categoría, precio ref., umbral stock bajo]
    B --> C[Agrega Variantes<br/>talla + color por cada combinación]
    C --> D{¿Stock inicial?}
    D -->|Sí| E[Registra Entrada de mercancía<br/>variante, cantidad, costo unitario, fecha]
    E --> F[Recalcula costo_promedio de la variante<br/>promedio ponderado móvil]
    F --> G[Registra movimiento_inventario<br/>tipo=entrada, +cantidad]
    G --> H[Variante con stock > 0<br/>lista para vender]
    D -->|No| I[Variante con stock 0<br/>visible pero no vendible]
```

### 4.2 Registro y confirmación de una venta

```mermaid
flowchart TD
    A[Vendedor abre Registrar venta] --> B[Busca productos y agrega líneas<br/>variante, cantidad, precio real, descuento %]
    B --> C{¿Cliente?}
    C -->|Contado sin cliente| D[metodo_pago = efectivo / transferencia]
    C -->|Con cliente| E{¿Aplica saldo a favor<br/>o paga a crédito?}
    E -->|Sí| F[Selecciona cliente obligatorio]
    E -->|No| D
    F --> D
    D --> G[Confirmar venta]
    G --> H[[BEGIN TRANSACTION]]
    H --> I[lockForUpdate sobre las variantes<br/>ordenadas por id]
    I --> J{¿stock >= cantidad<br/>en cada línea?}
    J -->|No| K[Error: stock insuficiente<br/>ROLLBACK]
    J -->|Sí| L{¿metodo_pago = credito?}
    L -->|Sí| M{¿Cliente en mora?<br/>RN-09}
    M -->|Sí y usuario = Empleado| N[Bloqueado<br/>ROLLBACK]
    M -->|Sí y usuario = Admin| O[Registra credito_autorizado_por]
    M -->|No| P
    O --> P
    L -->|No| P{¿saldo_favor_aplicado > 0?}
    P -->|Sí| Q[lockForUpdate sobre el cliente<br/>validar saldo disponible]
    P -->|No| R
    Q --> R[Inserta venta + venta_lineas<br/>con costo_unitario_snapshot]
    R --> S[Descuenta stock + movimiento_inventario tipo=venta]
    S --> T{¿Pago?}
    T -->|Crédito| U[credito_monto = restante<br/>credito_saldo_pendiente = restante]
    T -->|Saldo a favor| V[Descuenta clientes.saldo_favor<br/>+ saldo_favor_movimientos tipo=aplicado]
    T -->|Efectivo / transferencia| W[Sin registros adicionales]
    U --> X[[COMMIT]]
    V --> X
    W --> X
    X --> Y[Venta confirmada<br/>estado = confirmada]
```

### 4.3 Anulación de una venta (antes de la entrega)

```mermaid
flowchart TD
    A[Usuario abre el detalle de la venta] --> B{¿entregada_at IS NULL?}
    B -->|No, ya entregada| C[No se puede anular<br/>usar Devolución]
    B -->|Sí| D{¿Permiso?<br/>Admin = cualquiera / Empleado = solo propia}
    D -->|Sin permiso| E[Bloqueado]
    D -->|Con permiso| F[[BEGIN TRANSACTION]]
    F --> G[lockForUpdate sobre las variantes de la venta]
    G --> H[Reintegra stock + movimiento_inventario tipo=anulacion, +cantidad]
    H --> I{¿La venta usó saldo a favor?}
    I -->|Sí| J[Devuelve el saldo<br/>saldo_favor_movimientos tipo=generado + clientes.saldo_favor]
    I -->|No| K
    J --> K{¿Era a crédito?}
    K -->|Sí| L[Anula la deuda<br/>credito_saldo_pendiente = 0, marca la venta]
    K -->|No| M
    L --> M[estado = anulada<br/>anulada_at, anulada_por, motivo_anulacion]
    M --> N[[COMMIT]]
```

> Nota: si la venta a crédito ya tenía **abonos**, la anulación del MVP los deja registrados y
> genera saldo a favor por el monto abonado (nunca efectivo, RN-11). Confirmar con el negocio
> si este caso puede ocurrir en la práctica antes de la entrega.

### 4.4 Devolución (después de la entrega) y saldo a favor

```mermaid
flowchart TD
    A[Cliente regresa mercancía ya entregada] --> B[Administrador abre Devoluciones<br/>selecciona la venta]
    B --> C[Elige líneas y cantidades a devolver]
    C --> D[Evalúa validez<br/>defecto de fábrica, sin daño del cliente ni uso prolongado - RN-11]
    D --> E{¿Válida?}
    E -->|No| F[estado = rechazada<br/>saldo_generado = 0<br/>no cambia stock]
    E -->|Sí| G[[BEGIN TRANSACTION]]
    G --> H[Por cada línea: el Administrador marca<br/>reintegra_inventario sí/no - RN-13]
    H --> I{reintegra_inventario?}
    I -->|Sí| J[Reintegra stock + movimiento_inventario tipo=devolucion, +cantidad]
    I -->|No| K[No cambia stock<br/>producto dado de baja]
    J --> L
    K --> L[Calcula saldo_generado<br/>= suma de valor_unitario x cantidad]
    L --> M[lockForUpdate sobre el cliente]
    M --> N[Suma a clientes.saldo_favor<br/>+ saldo_favor_movimientos tipo=generado]
    N --> O[estado = validada]
    O --> P[[COMMIT]]
```

### 4.5 Abono a una venta a crédito

```mermaid
flowchart TD
    A[Administrador abre Créditos del cliente] --> B[Selecciona la venta a crédito<br/>con credito_saldo_pendiente > 0]
    B --> C[Ingresa monto y fecha del abono]
    C --> D[[BEGIN TRANSACTION]]
    D --> E[lockForUpdate sobre la venta]
    E --> F{¿monto <= credito_saldo_pendiente?}
    F -->|No| G[Error: sobrepago no permitido<br/>ROLLBACK]
    F -->|Sí| H[Inserta abono<br/>venta_id, monto, fecha, usuario_id]
    H --> I[credito_saldo_pendiente -= monto]
    I --> J{¿Llegó a 0?}
    J -->|Sí| K[Deuda saldada]
    J -->|No| L[Deuda parcial]
    K --> M[[COMMIT]]
    L --> M
```

### 4.6 Cálculo de la ganancia (RN-04)

```mermaid
flowchart LR
    A[venta_lineas] --> B["ganancia_linea =<br/>(precio_unitario x cantidad x (1 - desc%/100))<br/>- (costo_unitario_snapshot x cantidad)"]
    B --> C[ganancia_venta = suma de ganancia_linea]
    C --> D[Reporte de Ganancias RF-019<br/>por venta, por producto, comparación de periodos]
    B -.snapshot inmutable.-> E[Cambios futuros de costo o precio<br/>NO alteran ventas pasadas - RN-05]
```

---

## 5. Trazabilidad módulo → requisito

| Módulo | Requisitos cubiertos |
|--------|----------------------|
| Autenticación / Usuarios | RF-001, RF-002, RNF-003, RNF-007 |
| Categorías / Productos / Variantes | RF-003, RF-004 |
| Historial de producto | RF-016 |
| Entradas de mercancía | RF-005 |
| Ajustes de inventario | RF-006 |
| Alertas de stock bajo | RF-007 |
| Registrar venta / confirmar | RF-008, RF-009 |
| Anulación | RF-010 |
| Devoluciones | RF-011 |
| Saldo a favor | RF-011, RF-012 |
| Clientes | RF-013 |
| Créditos / Abonos | RF-014 |
| Control de mora | RF-015 |
| Reportes | RF-017, RF-018, RF-019 |
| Dashboard | RF-020 |
