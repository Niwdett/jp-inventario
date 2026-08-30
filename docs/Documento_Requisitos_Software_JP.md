# Documento de Requisitos de Software

**Sistema de Gestión de Inventario y Ventas — JP**
Versión 1.1 — Agosto de 2026

> Documento base de requisitos del negocio y del sistema. No incluye decisiones de modelo de datos, arquitectura ni tecnología.

## 1. Información general del proyecto

### 1.1 Nombre del proyecto
Sistema de Gestión de Inventario y Ventas — JP.

### 1.2 Cliente
Propietario y encargado del negocio "JP", tienda de ropa y calzado.

### 1.3 Descripción del negocio
JP es una tienda de ropa y calzado que opera actualmente en dos locales: una bodega principal, donde se concentra todo el stock, y un local secundario, donde el inventario corresponde a lo exhibido. La marca se originó en la venta de tenis y hoy comercializa ropa en general. Existe un plan, sin fecha definida, de consolidar ambos locales en uno solo, de mayor tamaño y mejor ubicado, actualmente en construcción.

### 1.4 Objetivo del proyecto
Reemplazar el proceso manual y duplicado de registro (cuaderno físico y hoja de Excel) por un sistema web que permita controlar el inventario en tiempo real, registrar ventas y crédito de clientes, y calcular ganancias de forma automática y confiable.

## 2. Problema actual

- Los productos y las ventas se registran en un cuaderno físico.
- En paralelo, esa misma información se transcribe a una hoja de Excel, que suma la ganancia diaria.
- No existe descuento real de stock al momento de vender: no hay inventario formal, solo lo que el dueño recuerda.
- No se registra el costo de la mercancía que entra; también se maneja "de memoria".
- No es posible consultar rápidamente cuánto stock hay disponible por talla y color.
- No existe un protocolo definido para cuando el inventario físico no coincide con lo esperado.
- El cálculo de ganancia depende enteramente de sumas manuales en Excel, sin una fórmula ni un registro sistemático.

## 3. Objetivos del sistema

### 3.1 Objetivo general
Desarrollar un sistema web de gestión de inventario y ventas para JP que permita controlar el stock en tiempo real, registrar ventas y crédito de clientes, y obtener reportes confiables de inventario y ganancias, eliminando la dependencia del cuaderno, el Excel y la memoria del dueño.

### 3.2 Objetivos específicos
- Registrar productos con sus variantes de talla y color, y su stock correspondiente.
- Descontar el inventario automáticamente al confirmar cada venta.
- Registrar el costo de cada entrada de mercancía.
- Calcular la ganancia real de cada venta y de cada producto, a partir del precio real vendido y el costo de esa unidad.
- Gestionar clientes y ventas a crédito, con registro de abonos y control de mora.
- Registrar devoluciones y el saldo a favor que estas generan.
- Generar reportes de ventas, inventario y ganancias por periodo.
- Diferenciar el acceso al sistema según el rol del usuario (Administrador o Empleado).

## 4. Alcance

### 4.1 Qué tendrá el sistema (MVP)
Gestión de productos y variantes, inventario con descuento automático, registro de ventas con precio real y descuentos, anulación de ventas antes de la entrega, devoluciones posteriores a la entrega con saldo a favor, clientes y crédito con control de mora, y reportes de ventas, inventario y ganancias. Detalle completo en la sección 8; justificación en la sección 13.

### 4.2 Qué queda para una segunda versión (V2)
Gestión completa de proveedores y compras, reporte de productos sin movimiento prolongado, y un límite máximo de crédito configurable por cliente.

### 4.3 Qué queda completamente fuera del alcance actual
Aplicación móvil nativa, venta por internet, lector de código de barras, manejo formal de múltiples ubicaciones o sucursales, y facturación electrónica.

## 5. Actores y roles

### 5.1 Administrador
Dueño del negocio o persona de su entera confianza. Control total:
- Crear, editar y eliminar productos, variantes y precios de referencia.
- Realizar y registrar entradas de inventario, incluyendo el costo de compra.
- Realizar ajustes manuales de inventario.
- Decidir si un producto dañado se vende con rebaja o se da de baja.
- Gestionar clientes, crédito y abonos.
- Consultar todos los reportes del sistema.
- Crear y administrar usuarios.

### 5.2 Empleado / Vendedor
Acceso limitado al módulo de ventas:
- Buscar productos y consultar la cantidad exacta de stock disponible.
- Registrar una venta, incluyendo el precio real de venta y el método de pago.
- No puede crear ni modificar productos, precios de referencia, ni hacer ajustes de inventario.
- No tiene acceso a los reportes financieros del negocio.

Toda venta registrada por un Empleado queda asociada a su usuario.

## 6. Procesos principales del negocio

### 6.1 Gestión de productos
Registro de productos con marca, categoría, código interno, precio de compra y precio de referencia de venta, foto y proveedor. Cada producto se maneja por variantes de talla y color, cada una con su propio stock.

### 6.2 Compras
**Entrada de mercancía (MVP):** el Administrador puede registrar producto, variante, cantidad, costo unitario y fecha de cada entrada, lo cual incrementa el inventario correspondiente. La gestión completa de proveedores — datos, factura o documento de compra, compra completa con múltiples productos, historial por proveedor y reportes — queda para la versión 2.

### 6.3 Inventario
El inventario es un stock único y global (ver RN-01). Se actualiza automáticamente con cada venta confirmada, admite ajustes manuales exclusivos del Administrador, y genera alertas visuales dentro del sistema cuando una variante llega a su umbral de stock bajo, configurable por producto. La notificación por canales externos (por ejemplo WhatsApp) no forma parte del MVP y queda como pendiente (sección 12).

### 6.4 Ventas
Registro de la venta de una o varias variantes de producto, con captura del precio real de la transacción (que puede incluir un descuento manual o por porcentaje), selección del método de pago (efectivo, transferencia o crédito) y descuento automático del inventario correspondiente. Mientras la mercancía no haya sido entregada al cliente, la venta puede anularse, reintegrando automáticamente el inventario. Una vez entregada, cualquier devolución se rige por el proceso descrito en 6.7.

### 6.5 Crédito
Registro de ventas a crédito por cliente, con abonos parciales fechados y bloqueo automático de nuevo crédito para clientes en mora.

### 6.6 Clientes
Registro de datos básicos del cliente y su historial de compras.

### 6.7 Devoluciones
Registro de devoluciones de mercancía ya entregada al cliente — a diferencia de la anulación (6.4), que solo aplica antes de la entrega. Son válidas las devoluciones por defecto de fábrica, sin daño causado por el cliente ni uso prolongado, con reintegro de la prenda al inventario cuando corresponda.

### 6.8 Saldo a favor
Generación de saldo a favor por devoluciones válidas, utilizable en compras posteriores dentro del negocio; nunca se entrega en efectivo.

### 6.9 Reportes
Consulta de ventas por periodo, inventario disponible y ganancias por venta y por producto, con comparación entre periodos.

## 7. Reglas de negocio

| ID | Regla de negocio |
|----|-------------------|
| RN-01 | El inventario es un stock único y global. No se maneja por ubicación ni se registran traslados entre locales. |
| RN-02 | Una venta confirmada descuenta el inventario automáticamente y en tiempo real. |
| RN-03 | Cada venta conserva el precio real al que se realizó la transacción, el cual puede diferir del precio de referencia del producto. |
| RN-04 | La ganancia de una venta se calcula como precio real de venta menos el costo de compra de esa unidad específica. El resultado puede ser negativo. |
| RN-05 | El costo de las unidades ya vendidas **nunca cambia**: cada línea de venta guarda el costo con el que se calculó su ganancia y ese valor queda congelado (RN-04). Para valorar el inventario **en existencia** y costear las ventas futuras, el sistema usa **promedio ponderado móvil**: cada entrada de mercancía recalcula el costo promedio de la variante combinando el costo del stock que ya había con el de las unidades que entran (confirmado con el negocio el 2026-08-29; detalle en `Decisiones_Tecnicas_JP.md §A1`). |
| RN-06 | Solo el usuario con rol Administrador puede crear o modificar productos y precios, y realizar ajustes manuales de inventario. |
| RN-07 | El usuario con rol Empleado/Vendedor puede consultar productos, ver la cantidad exacta de stock disponible y registrar ventas. |
| RN-08 | Toda venta queda asociada al usuario que la registró. |
| RN-09 | Un cliente que tenga una deuda con un atraso superior a 15 días no puede realizar nuevas compras a crédito mientras permanezca en mora. |
| RN-10 | Ante una discrepancia entre el conteo físico y el inventario registrado, el conteo físico prevalece y el sistema se actualiza según este. |
| RN-11 | Una devolución válida (defecto de fábrica, sin daño causado por el cliente ni uso prolongado) genera saldo a favor. No se realizan devoluciones en efectivo. |
| RN-12 | El saldo a favor puede utilizarse para adquirir otra u otras prendas de igual o menor valor; si el nuevo producto excede el saldo, el cliente completa la diferencia en dinero. |
| RN-13 | La clasificación de un producto dañado como "vendible con rebaja" o "no vendible" es una decisión manual y exclusiva del Administrador. |
| RN-14 | El umbral de alerta de "stock bajo" es configurable por producto; no existe un valor único global. |
| RN-15 | Los ajustes manuales de inventario **no registran qué usuario** los hizo (solo el Administrador puede hacerlos); sí quedan con **fecha y motivo** (`ajustes_inventario`, `movimientos_inventario`). La anulación de una entrada (§A4) **sí** registra el usuario: es una corrección de captura, no un conteo físico. |

## 8. Requisitos funcionales

### 8.1 Requisitos del MVP

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-001 | Autenticación de usuarios (inicio de sesión para Administrador y Empleado). | MVP |
| RF-002 | Gestión de usuarios y roles: creación y edición de usuarios, asignación de rol. | MVP |
| RF-003 | Gestión de productos: creación, edición y listado, incluyendo precio de referencia, categoría y código interno. | MVP |
| RF-004 | Gestión de variantes de producto por talla y color, con stock independiente por cada combinación. | MVP |
| RF-005 | Registro de entradas de inventario (producto, variante, cantidad, costo unitario y fecha). | MVP |
| RF-006 | Ajuste manual de inventario, disponible únicamente para el rol Administrador. | MVP |
| RF-007 | Configuración y disparo de alertas visuales de stock bajo, con umbral definido por producto. | MVP |
| RF-008 | Registro de ventas: selección de producto/variante, captura del precio real de venta, aplicación opcional de un porcentaje de descuento, y selección del método de pago. | MVP |
| RF-009 | Descuento automático del inventario al confirmar una venta. | MVP |
| RF-010 | Anulación de una venta antes de que la mercancía haya sido entregada al cliente, con reintegro automático al inventario. | MVP |
| RF-011 | Registro de devoluciones de mercancía ya entregada, con generación de saldo a favor cuando la devolución es válida. | MVP |
| RF-012 | Aplicación de saldo a favor como medio de pago en una compra posterior. | MVP |
| RF-013 | Gestión de clientes: registro de nombre, teléfono, cédula y datos básicos. | MVP |
| RF-014 | Gestión de crédito por cliente, con registro de abonos parciales y su fecha. | MVP |
| RF-015 | Bloqueo automático de nuevas ventas a crédito para clientes en mora, según RN-09. | MVP |
| RF-016 | Historial de producto: fecha de creación y registro de las modificaciones realizadas sobre su información. El historial de ventas del producto queda cubierto por los registros de venta, no por este historial. | MVP |
| RF-017 | Reporte de ventas por periodo (día, semana, mes). | MVP |
| RF-018 | Reporte de inventario disponible. | MVP |
| RF-019 | Reporte de ganancias por venta y por producto, con comparación entre periodos. | MVP |
| RF-020 | Panel (dashboard) con los indicadores principales del negocio. | MVP |

### 8.2 Requisitos de versión 2 (V2)

| ID | Descripción | Prioridad |
|----|-------------|-----------|
| RF-021 | Gestión completa de proveedores: registro de datos y tipo de mercancía. | V2 |
| RF-022 | Registro de compras a proveedores con historial consultable por proveedor. | V2 |
| RF-023 | Reporte de productos sin movimiento durante un periodo prolongado. | V2 |
| RF-024 | Límite máximo de crédito configurable por cliente (valor exacto pendiente de definir — sección 12). | V2 |

## 9. Requisitos no funcionales

| ID | Descripción |
|----|-------------|
| RNF-001 | El sistema debe ser una aplicación web, accesible desde navegador. |
| RNF-002 | La interfaz debe ser responsive y funcionar correctamente desde un celular. |
| RNF-003 | El acceso a cada módulo debe estar restringido según el rol del usuario autenticado. |
| RNF-004 | Las operaciones de venta e inventario deben reflejarse en tiempo real. |
| RNF-005 | La integridad de los datos de inventario y ventas debe garantizarse ante operaciones concurrentes (por ejemplo, dos ventas simultáneas sobre el mismo producto). |
| RNF-006 | El sistema debe mantener copias de seguridad periódicas de la información. |
| RNF-007 | Las credenciales y datos de los usuarios deben almacenarse de forma segura. |
| RNF-008 | El sistema debe mantenerse y documentarse de forma que permita incorporar los requisitos de V2 sin rediseños mayores. |
| RNF-009 | El sistema debe ser usable por personas sin conocimientos técnicos, incluyendo el dueño del negocio y el personal de ventas. |

## 10. Diseño e identidad visual

- Marca actual: "JP", originada en la venta de tenis; el cliente desea evolucionarla porque el negocio hoy es ropa en general, no solo calzado.
- Colores actuales de la marca: blanco y negro.
- Incluido en el alcance del proyecto: propuesta visual para la interfaz (colores, tipografía) y un logotipo sencillo para uso dentro del sistema.
- No incluido: rediseño completo de identidad de marca, manual de marca, papelería ni estrategia de marca.
- La interfaz debe ser accesible desde computador y celular, mediante navegador web (responsive), sin aplicación nativa por ahora.

## 11. Requisitos fuera del alcance

- Aplicación móvil nativa.
- Venta por internet / comercio electrónico.
- Lector de código de barras.
- Manejo formal de múltiples sucursales (el sistema opera con un único stock global; ver RN-01).
- Facturación electrónica.
- Rediseño completo de identidad de marca (logo, manual de marca) como entregable de diseño gráfico independiente.

## 12. Requisitos pendientes

### Decisiones tomadas (2026-08-29)

- **Costeo (RN-05):** promedio ponderado móvil, con el costo de cada venta congelado. Ver RN-05 y `Decisiones_Tecnicas_JP.md §A1`.
- **Mora (RN-09):** el atraso se cuenta como **> 15 días desde la fecha de la venta** (no hay plazo formal pactado por venta). `Cliente::DIAS_MORA = 15`.
- **Vencimiento del saldo a favor:** **no vence**; queda disponible hasta que el cliente lo use. Sí puede combinarse con una compra a crédito (cerrado en Sprint 4).
- **Descuento del Empleado (RF-008 / decisión G4):** **sin tope**; se registra el precio real pactado y queda asociado al vendedor que lo hizo.
- **Sobreventa simultánea:** resuelta con transacción + bloqueo pesimista + guarda de stock no negativo (`Decisiones_Tecnicas_JP.md §B1`).

### Aún abiertos

- Si la anulación de una venta aplica sin límite de tiempo desde la entrega, o si debe restringirse pasado cierto periodo.
- El valor exacto del límite máximo de crédito por cliente (RF-024, V2).
- La nomenclatura completa del código interno de producto (color y talla), más allá del prefijo por categoría ya definido.
- El criterio exacto de "uso prolongado" para rechazar una devolución (referencia actual: 2 días, no confirmada).
- El canal de notificación externo (WhatsApp u otro) para las alertas de stock bajo.

## 13. Matriz de trazabilidad / prioridad

| ID | Requisito | Prioridad | Justificación |
|----|-----------|-----------|----------------|
| RF-001 | Autenticación de usuarios | MVP | Sin esto ningún otro requisito con control de acceso es posible. |
| RF-002 | Gestión de usuarios y roles | MVP | Condición para separar Administrador y Empleado (RN-06, RN-07). |
| RF-003 | Gestión de productos | MVP | Base de toda la operación; resuelve el problema actual de control manual. |
| RF-004 | Gestión de variantes talla/color | MVP | El negocio vende ropa y calzado por variantes; sin esto no hay stock real. |
| RF-005 | Entradas de inventario con costo | MVP | Hoy no se registra el costo; es un problema actual explícito a resolver. |
| RF-006 | Ajuste manual de inventario | MVP | Necesario para reflejar la realidad física del stock (RN-10). |
| RF-007 | Alertas de stock bajo | MVP | Necesidad expresada directamente por el cliente. |
| RF-008 | Registro de ventas con precio real | MVP | Reemplaza el registro en cuaderno; base del cálculo de ganancia. |
| RF-009 | Descuento automático de inventario | MVP | Resuelve el problema central: hoy no hay descuento real de stock. |
| RF-010 | Anulación de venta (antes de entrega) | MVP | Caso de uso frecuente, distinto de la devolución (RF-011). |
| RF-011 | Devolución (después de entrega) con saldo a favor | MVP | Política de devoluciones ya definida y usada por el negocio hoy. |
| RF-012 | Aplicación de saldo a favor | MVP | Consecuencia directa de RF-011. |
| RF-013 | Gestión de clientes | MVP | El negocio opera hoy con clientes fijos y ventas a crédito. |
| RF-014 | Crédito y abonos | MVP | El crédito es parte diaria de la operación actual, no es opcional. |
| RF-015 | Bloqueo de crédito por mora | MVP | Regla de negocio ya definida y usada informalmente hoy. |
| RF-016 | Historial de producto | MVP | Requisito confirmado por el cliente en el levantamiento. |
| RF-017 | Reporte de ventas por periodo | MVP | Objetivo explícito: reemplazar el cálculo manual en Excel. |
| RF-018 | Reporte de inventario disponible | MVP | Resuelve la dificultad actual de conocer el stock real. |
| RF-019 | Reporte de ganancias | MVP | Objetivo explícito del cliente; requiere RF-008 y RN-04. |
| RF-020 | Panel / dashboard | MVP | Punto de entrada que consolida los reportes anteriores. |
| RF-021 | Gestión completa de proveedores | V2 | Hoy se maneja "de memoria"; no bloquea la operación diaria. |
| RF-022 | Registro de compras con historial | V2 | Depende de RF-021; valioso pero no urgente. |
| RF-023 | Productos sin movimiento | V2 | Reporte de valor analítico, no operativo del día a día. |
| RF-024 | Límite máximo de crédito | V2 | El valor exacto está pendiente de definir con el cliente. |

## 14. Criterios de aceptación generales

- El sistema permite registrar una venta completa (producto, variante, precio real, método de pago) sin errores y sin pasos innecesarios.
- El stock se actualiza de forma automática y visible inmediatamente después de cada venta o ajuste manual.
- Un usuario con rol Empleado no puede acceder a las pantallas de administración de productos, precios ni reportes financieros.
- El sistema calcula correctamente la ganancia de una venta usando el precio real registrado y el costo de la unidad vendida (RN-04).
- Un cliente en mora no puede completar una nueva venta a crédito sin intervención manual del Administrador (RN-09).
- Toda devolución válida genera un saldo a favor visible en el perfil del cliente, sin opción de reembolso en efectivo (RN-11).
- Los reportes de ventas, inventario y ganancias reflejan la información registrada hasta el momento de la consulta.

## 15. Glosario

| Término | Definición |
|---------|-----------|
| MVP | Producto mínimo viable: el conjunto de funcionalidades imprescindible para que el sistema resuelva el problema actual del negocio. |
| Stock global | Modelo de inventario en el que existe una única cantidad disponible por variante de producto, sin distinción de ubicación física. |
| Variante | Combinación específica de talla y color de un mismo producto, cada una con su propio stock. |
| Precio de referencia | Precio de venta sugerido que se guarda en la ficha del producto, sin ser obligatorio en cada venta. |
| Precio real de venta | Monto exacto al que se concretó una venta en particular, que puede coincidir o no con el precio de referencia. |
| Anulación | Cancelación de una venta antes de que la mercancía haya sido entregada al cliente; el stock vuelve automáticamente al inventario. |
| Devolución | Recepción de una mercancía ya entregada previamente al cliente, sujeta a las condiciones establecidas por el negocio; genera saldo a favor si es válida. |
| Saldo a favor | Monto que recibe un cliente por una devolución válida, utilizable únicamente en compras futuras dentro del negocio. |
| Mora | Situación de un cliente con pagos a crédito atrasados más allá del periodo definido (15 días). |
| Costo histórico | Costo de compra correspondiente a la unidad específica que se vendió, aunque el costo del proveedor haya cambiado después. |
| RF | Requisito funcional: una capacidad concreta que el sistema debe ejecutar. |
| RNF | Requisito no funcional: una condición de calidad o restricción que el sistema debe cumplir. |
| RN | Regla de negocio: una condición o política del negocio que el sistema debe respetar. |
