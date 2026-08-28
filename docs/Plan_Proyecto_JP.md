# Plan de Proyecto — Sistema de Gestión de Inventario y Ventas (JP)

Desarrollo con Laravel sobre un caso de uso real, como proyecto de portafolio profesional.
**Versión 2.0** — duración total estimada: 8 semanas (≈ 2 meses, 6 h/día).

## 0. Qué cambió respecto a la versión anterior

Esta versión reduce el proyecto de ~18–20 semanas a 8 semanas, sobre la base del sistema real de "JP" (tienda de ropa y calzado), con requisitos ya levantados y roles ya definidos (Administrador y Empleado/Vendedor).

Ajustes principales:
- PHP y Laravel se fusionan en una sola fase de aprendizaje aplicado.
- Las decisiones de integridad de datos, concurrencia y manejo de dinero se adelantan a la Fase 3 (diseño técnico).
- Se elimina la API REST, Postman y Docker del alcance del MVP.
- El módulo de "Caja" formal y la gestión completa de Proveedores se mueven a V2.
- El despliegue se hace en un hosting gestionado (Railway u otro PaaS), no en un servidor Linux administrado manualmente.
- La fase de "Mejoras" deja de ser una fase formal del cronograma de 2 meses; las funcionalidades de V2 quedan como hoja de ruta posterior.

## 1. El proyecto real: JP — tienda de ropa y calzado

El sistema reemplaza un proceso manual y duplicado (cuaderno físico + Excel). El negocio opera con un stock único y global, y tiene dos roles de usuario:

| Rol | Puede hacer |
|-----|-------------|
| Administrador | Control total: productos, variantes, precios, entradas de inventario, ajustes manuales, clientes, crédito, todos los reportes, gestión de usuarios |
| Empleado / Vendedor | Buscar productos, ver stock exacto, registrar ventas con su usuario asociado. Sin acceso a edición de productos ni a reportes financieros |

## 2. Decisiones técnicas consolidadas

| Decisión | Resolución |
|----------|------------|
| Entorno de desarrollo | Windows con XAMPP o Laragon. No se administra Linux directamente. |
| Despliegue | Hosting gestionado tipo PaaS (Railway como opción principal; cPanel como alternativa). Se descarta Windows Server + IIS y la administración manual de un VPS Linux. |
| API REST | No se construye en el MVP. Se reconsidera solo si surge una necesidad real. |
| Postman / API Resources | Fuera de la pila del sistema real. |
| Docker | Fuera de alcance. |
| Código de barras | Fuera de alcance total (confirmado por el cliente), no solo del MVP. |
| Modo oscuro | Eliminado del plan por completo. |
| Manejo de dinero | Todos los campos monetarios usan tipo `decimal`, nunca `float`. |
| Historial de producto vs. ventas | Dos conceptos separados: historial de modificaciones del producto por un lado, registros de venta por otro. |
| Anulación vs. devolución | Anulación = cancelar antes de la entrega (vuelve a stock); devolución = producto ya entregado que el cliente regresa (genera saldo a favor). |
| Costeo de inventario | Se define una estrategia explícita (FIFO o costo promedio) en la Fase 3, antes de tocar el módulo de ventas. |
| Integridad y concurrencia | Estrategia de borrado (soft-delete para productos con ventas asociadas) y manejo de transacciones se definen en la Fase 3, no en testing. |
| Pruebas automatizadas | Un conjunto mínimo de feature tests es parte formal del MVP. |
| Alertas de stock bajo | MVP: alerta visual dentro del sistema. Notificación externa (WhatsApp) queda pendiente. |
| Identidad visual | Incluido: propuesta visual simple (colores, tipografía, logotipo sencillo). No incluido: branding profesional completo. |

## 3. Alcance

### 3.1 MVP (obligatorio en las 8 semanas)
- Autenticación y gestión de usuarios/roles (Administrador, Empleado).
- Gestión de productos y variantes por talla y color, con stock independiente por variante.
- Entradas de inventario con costo de compra, y ajustes manuales (solo Administrador).
- Alertas visuales de stock bajo, con umbral configurable por producto.
- Registro de ventas con precio real, descuento opcional y método de pago; descuento automático de inventario.
- Anulación de venta antes de la entrega, con reintegro automático al inventario.
- Devoluciones posteriores a la entrega, con generación de saldo a favor (nunca en efectivo).
- Aplicación de saldo a favor como medio de pago en una compra posterior.
- Gestión de clientes, crédito con abonos parciales y bloqueo automático por mora.
- Historial de producto (creación y modificaciones, sin mezclar con ventas).
- Reportes de ventas por periodo, inventario disponible y ganancias, más un dashboard.

### 3.2 V2 (explícitamente fuera de las 8 semanas)
- Gestión completa de proveedores.
- Registro de compras con historial consultable por proveedor.
- Reporte de productos sin movimiento prolongado.
- Límite máximo de crédito configurable por cliente.

### 3.3 Fuera de alcance total
- Aplicación móvil nativa y venta por internet.
- Lector de código de barras.
- Manejo formal de múltiples sucursales o ubicaciones.
- Facturación electrónica.
- Rediseño completo de identidad de marca / manual de marca.
- API REST, Docker y modo oscuro.

## 4. Pila tecnológica

| Capa | Tecnología | Notas |
|------|-----------|-------|
| Backend | PHP 8 + Laravel | Framework principal |
| Base de datos | MySQL | Vía XAMPP/Laragon en desarrollo |
| Frontend | Blade + Bootstrap/Tailwind | Se define en Fase 2 |
| Control de versiones | Git + GitHub | Repositorio público, ramas por sprint |
| Entorno local | XAMPP o Laragon | Windows, sin administración de Linux |
| Pruebas | PHPUnit (feature tests mínimos) | Parte formal del MVP |
| Documentación | Notion o carpeta de proyecto | — |
| Despliegue | Railway (PaaS) — cPanel como alternativa | Sin servidor propio que administrar |

## 5. Fases del proyecto (8 semanas / 42 días hábiles)

### Fase 0 — Organización personal (0.5 día)
**Objetivo:** dejar listo el entorno de trabajo y la rutina.
- Definir el horario diario.
- Crear la estructura de carpetas del proyecto.
- Configurar la bitácora de aprendizaje diario.
**Entregable:** estructura de carpetas creada y horario definido.

### Fase 1 — Cierre del documento de requisitos (0.5 día)
**Objetivo:** aplicar las correcciones ya identificadas y cerrar el documento sin ambigüedades.
**Entregable:** documento de requisitos v1.1 cerrado (ver `Documento_Requisitos_Software_JP.md`).

### Fase 2 — Diseño funcional (2.5 días)
**Objetivo:** convertir los requisitos en módulos, flujos y pantallas concretas.
- Módulos del Administrador: Dashboard, Productos (Productos, Variantes, Inventario), Ventas, Clientes (Clientes, Créditos, Abonos), Reportes, Usuarios.
- Módulos del Empleado: Ventas (buscar producto, seleccionar variante, crear venta, seleccionar pago, confirmar venta).
- Diagramar flujos: crear producto → agregar variantes → establecer stock → registrar venta → descontar stock → calcular costo → calcular ganancia.
**Entregable:** árbol de módulos por rol y diagrama de flujos principales.

### Fase 3 — Diseño técnico (4 días)
**Objetivo:** tomar antes de programar las decisiones críticas: costeo, integridad, concurrencia.
- Diagrama Entidad-Relación completo.
- Estrategia de costeo de inventario (FIFO o costo promedio).
- Estrategia de borrado: soft-delete para productos con ventas asociadas.
- Manejo de concurrencia (transacciones) para evitar sobreventa.
- Convención de código interno de producto.
- Arquitectura MVC estándar de Laravel y convenciones de nombres.
**Entregable:** diagrama ER completo y documento de decisiones técnicas críticas.

### Fase 4 — Preparación del entorno (1.5 días)
- Instalar VS Code, Git, PHP, Composer, Laravel, Node.js, MySQL, XAMPP o Laragon.
- Configurar Git, GitHub y llaves SSH.
- Configurar `.env` y confirmar que nunca se versionan credenciales.
**Entregable:** proyecto Laravel de prueba corriendo en local, conectado a MySQL.

### Fase 5 — Aprendizaje aplicado de Laravel (8 días)
**Objetivo:** aprender el framework construyendo directamente sobre los módulos del proyecto real.
- Migraciones, seeders y factories sobre el modelo ya diseñado en la Fase 3.
- Eloquent y relaciones entre modelos.
- Blade, controladores, validaciones y subida de imágenes.
- Autenticación, middleware y roles.
- Transacciones de Eloquent (concurrencia).
- PHPUnit: feature tests mínimos.
**Entregable:** login funcional con roles y CRUD de productos/variantes sobre el modelo real.

### Fase 6 — Planificación de sprints (1 día)
- Crear el repositorio en GitHub con el proyecto Laravel base.
- Definir la estrategia de ramas (main, develop, feature/*).
- README inicial y tablero de tareas con el backlog de los 5 sprints.
**Entregable:** repositorio creado con README inicial y tablero de tareas.

### Fase 7 — Desarrollo del MVP (16 días)

| Sprint | Días | Alcance | Definición de "hecho" |
|--------|------|---------|------------------------|
| 1 | 2 | Autenticación, usuarios, roles | Un usuario con rol definido inicia sesión y ve solo lo que su rol permite |
| 2 | 4 | Productos, variantes, inventario, alertas | CRUD de productos con variantes talla/color, entradas de inventario y alerta visual de stock bajo |
| 3 | 4 | Ventas y anulación | Se registra una venta completa, descuenta stock automáticamente, y puede anularse antes de la entrega |
| 4 | 4 | Devoluciones, saldo a favor, clientes y crédito | Una devolución genera saldo a favor aplicable; un cliente en mora queda bloqueado para crédito nuevo |
| 5 | 2 | Historial de producto, reportes y dashboard | El dashboard refleja ventas, inventario y ganancias reales del sistema |

**Entregable:** MVP funcional de punta a punta, cubriendo RF-001 a RF-020.

### Fase 8 — Testing (3 días)
- Ejecutar y ampliar los feature tests mínimos de la Fase 5.
- Verificar venta sin stock, eliminación de producto con ventas asociadas, dos ventas simultáneas.
- Verificar cálculo de ganancia (RN-04) y bloqueo por mora (RN-09).
**Entregable:** checklist de casos de prueba ejecutados, con defectos corregidos.

### Fase 9 — Despliegue (2 días)
- Desplegar en Railway (o cPanel) con MySQL gestionado.
- Configurar variables de entorno de producción y backups periódicos.
- Configurar logs de errores básicos.
**Entregable:** sistema desplegado y accesible por URL pública.

### Fase 10 — Portafolio (3 días)
- README profesional, capturas de pantalla, video demo (2–4 min).
- Diagrama de arquitectura y diagrama ER.
- Manual de instalación y manual de usuario.
**Entregable:** repositorio publicado, listo para CV/LinkedIn.

### Fase 11 — Preparación laboral (en paralelo, durante todo el proyecto)
- Actualizar el CV conforme avanzan las fases.
- Publicar hitos en LinkedIn.
- Seguir postulando activamente.

## 6. Cronograma consolidado

| Fase | Nombre | Duración |
|------|--------|----------|
| 0 | Organización personal | 0.5 día |
| 1 | Cierre del documento de requisitos | 0.5 día |
| 2 | Diseño funcional | 2.5 días |
| 3 | Diseño técnico | 4 días |
| 4 | Preparación del entorno | 1.5 días |
| 5 | Aprendizaje aplicado de Laravel | 8 días |
| 6 | Planificación de sprints | 1 día |
| 7 | Desarrollo del MVP (5 sprints) | 16 días |
| 8 | Testing | 3 días |
| 9 | Despliegue | 2 días |
| 10 | Portafolio | 3 días |
| 11 | Preparación laboral | Paralela a todo el proyecto |

Total estimado: 42 días hábiles ≈ 8 semanas (2 meses) trabajando 6 horas diarias.

## 7. Rutina diaria recomendada (6 horas)

| Horario | Actividad |
|---------|-----------|
| 09:00 – 10:00 | Estudiar/diseñar el tema del día |
| 10:00 – 11:00 | Practicar o continuar el desarrollo |
| 11:00 – 12:00 | Aplicar el conocimiento al sprint en curso |
| 13:30 – 14:30 | Continuar el desarrollo del sprint |
| 14:30 – 15:00 | Documentar lo avanzado |
| 15:00 – 16:00 | Git, pruebas, refactorización o repaso |

## 8. Pendientes que quedan abiertos

- Límite de tiempo para anular una venta después de la entrega.
- Vencimiento del saldo a favor y si puede combinarse con una compra a crédito.
- Criterio exacto de "uso prolongado" para rechazar una devolución.
- Política de precio mínimo o descuento máximo permitido a un Empleado.

## 9. Criterios de éxito del proyecto

- El sistema resuelve el problema real de JP: elimina el cuaderno, el Excel y el cálculo manual de ganancia.
- Cada fase tiene un entregable verificable.
- Las decisiones técnicas críticas están documentadas antes del sprint 3.
- El sistema está desplegado y accesible, no solo en localhost.
- Existe evidencia de avance constante en LinkedIn/CV durante la búsqueda de empleo.
