# Decisiones Técnicas — Sistema JP (Fase 3)

**Versión 0.1 (en construcción)** — Agosto de 2026

> Documento de la Fase 3 del plan de proyecto. Registra las decisiones técnicas críticas
> que, según `CLAUDE.md`, deben quedar documentadas **antes** de implementar los módulos
> correspondientes. El diagrama Entidad‑Relación completo se consolida al final, una vez
> cerradas todas las decisiones.
>
> Estado de los bloques:
> - Bloque A · Modelo de dominio — **CERRADO**
> - Bloque B · Integridad — pendiente
> - Bloque C · Dinero del cliente — pendiente

---

## Bloque A — Modelo de dominio

### A1. Estrategia de costeo del inventario

**Decisión:** costo **promedio ponderado móvil** por variante, con **snapshot del costo en cada línea de venta**.

- Cada variante mantiene un campo `costo_promedio`.
- Al registrar una **entrada de mercancía** se recalcula:

  ```
  costo_promedio_nuevo = (stock_actual · costo_promedio_actual + cantidad_entrada · costo_unitario_entrada)
                         / (stock_actual + cantidad_entrada)
  ```

- Al **confirmar una venta**, el `costo_promedio` vigente de la variante se copia a la
  línea de venta (`venta_lineas.costo_unitario_snapshot`) y ya no cambia nunca.

**Alternativa descartada:** FIFO por lotes/capas de inventario. Da el costo exacto de la
unidad física vendida, pero exige una tabla de capas, lógica de consumo de capas y reparto
de devoluciones entre capas. Complejidad que no aporta valor al negocio real de JP (tienda
de ropa y calzado). El plan de proyecto ofrece "FIFO **o** costo promedio" como opciones
equivalentes.

**Reglas de negocio cubiertas:** RN‑04 (hay un costo claro atribuido a cada unidad vendida),
RN‑05 (ver A2).

### A2. Manejo del costo histórico por unidad

El costo vive en tres lugares, cada uno con una función distinta:

| Lugar | Campo | Cuándo cambia | Función |
|-------|-------|---------------|---------|
| `variantes` | `costo_promedio` (decimal) | En cada entrada de mercancía | Valorar inventario actual + alimentar el snapshot |
| `entradas_inventario` | `costo_unitario` (decimal) | Nunca (registro histórico) | Auditoría de compras y del recálculo |
| `venta_lineas` | `costo_unitario_snapshot` (decimal) | Nunca (se fija al confirmar la venta) | Calcular la ganancia de esa venta, de forma permanente |

**Fórmula de ganancia de una línea de venta (RN‑04):**

```
ganancia_linea = (precio_real_unitario − descuento_unitario) · cantidad
                 − costo_unitario_snapshot · cantidad
```

El resultado puede ser negativo (RN‑04 lo permite explícitamente).

**Consecuencia de RN‑05:** cambiar el costo de compra de un producto recalcula
`variantes.costo_promedio` para ventas futuras, pero **no modifica ninguna fila de
`venta_lineas` existente**. El historial de ganancias es inmutable por diseño, sin lógica
especial.

**Tipos monetarios:** todos los campos de dinero usan `decimal` (nunca `float`), coherente
con `CLAUDE.md` y el plan de proyecto. Precisión a definir en el ER (propuesta:
`decimal(12, 2)` para importes; `decimal(12, 4)` para costos unitarios si se requiere más
resolución en el promedio).

### A3. Estructura de productos y variantes

**Regla base:** un producto **siempre** tiene al menos una variante. Una prenda de talla y
color únicos se modela como una variante `"Única" / "Única"`. Así el módulo de ventas no
tiene casos especiales.

#### `productos`

| Campo | Tipo | Notas |
|-------|------|-------|
| `nombre` | string | |
| `marca` | string | |
| `categoria_id` | FK → `categorias` | Ver A3.2 |
| `codigo_interno` | string | Prefijo por categoría ya definido; resto de la nomenclatura pendiente (Requisitos §12) |
| `precio_referencia` | decimal | RF‑003; no obligatorio en cada venta |
| `foto` | string (path) | |
| `umbral_stock_bajo` | int | **En producto, no en variante** (RN‑14: configurable por producto) |
| `proveedor` | string, nullable | Campo simple en el MVP; la gestión real de proveedores es V2 |
| soft-delete | — | Se decide en el Bloque B |

#### `variantes`

| Campo | Tipo | Notas |
|-------|------|-------|
| `producto_id` | FK → `productos` | |
| `talla` | string | Texto libre (ver A3.1) |
| `color` | string | Texto libre (ver A3.1) |
| `codigo` | string, nullable | Nomenclatura pendiente (Requisitos §12) |
| `stock` | int | Cantidad única y global (RN‑01) |
| `costo_promedio` | decimal | Ver A1 |
| — | — | **unique(`producto_id`, `talla`, `color`)** |

#### `categorias`

| Campo | Tipo | Notas |
|-------|------|-------|
| `nombre` | string | |
| `prefijo_codigo` | string | Prefijo del `codigo_interno` de sus productos |

#### Decisiones puntuales de A3

| # | Pregunta | Decisión |
|---|----------|----------|
| A3.1 | ¿`talla` y `color` como texto libre o tablas de catálogo? | **Texto libre** en `variantes`. Calzado (35–45) y ropa (XS–XXL) usan escalas distintas; un catálogo rígido estorba. Se puede añadir un catálogo después sin romper el modelo. |
| A3.2 | ¿`categoria` como string o tabla? | **Tabla `categorias`**: son pocas y estables, y el `codigo_interno` deriva un prefijo de la categoría. |
| A3.3 | ¿`stock` en `variantes` o tabla `inventario` aparte? | **Columna en `variantes`**. El stock es global (RN‑01); una tabla aparte solo tendría sentido con múltiples ubicaciones (fuera de alcance). |
| A3.4 | ¿Libro `movimientos_inventario`? | **Sí**, pero el diseño se define en el Bloque B (historial + concurrencia). `variantes.stock` será un valor derivado auditable, no la única fuente de verdad. |
