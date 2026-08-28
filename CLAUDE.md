# CONTEXTO DEL PROYECTO

El proyecto es un **Sistema de Gestión de Inventario y Ventas para el negocio real JP**, una tienda de ropa y calzado.

El sistema nace para reemplazar el proceso manual actual basado principalmente en:

- Cuaderno físico.
- Excel.
- Memoria del propietario.

El sistema debe resolver principalmente:

- Falta de control real del inventario.
- Falta de registro sistemático del costo de mercancía.
- Registro manual y duplicado de ventas.
- Dificultad para conocer el stock disponible.
- Cálculo manual de ganancias.
- Gestión manual de clientes y ventas a crédito.

El proyecto tiene un doble objetivo:

### Objetivo 1 — Negocio

Construir una aplicación web realmente útil para el funcionamiento del negocio.

### Objetivo 2 — Aprendizaje y portafolio

Utilizar el proyecto como proyecto real de aprendizaje y como pieza profesional de portafolio para demostrar competencias de desarrollo web/backend.

Debes tener siempre presentes ambos objetivos.

---

# STACK GENERAL

La solución está orientada a:

- PHP.
- Laravel.
- MySQL.
- Blade.
- Tailwind CSS v4 (decidido en Fase 3; ver `docs/Decisiones_Tecnicas_JP.md` §F2).
- Laravel Breeze (stack Blade) como scaffolding de autenticación, sin registro público.
- Git.
- GitHub.
- PHPUnit / feature tests.
- Desarrollo en Windows.
- Hosting gestionado para producción.

El proyecto NO utilizará inicialmente:

- API REST.
- Docker.
- Código de barras.
- Aplicación móvil nativa.
- Comercio electrónico.
- Facturación electrónica.
- Administración de servidores Linux.
- Modo oscuro como funcionalidad del proyecto.

No introduzcas ninguna de estas tecnologías o funcionalidades sin una razón explícita y una decisión posterior del usuario.

---

# DECISIONES YA TOMADAS

Estas decisiones deben considerarse como establecidas salvo que el usuario las cambie explícitamente:

- El entorno principal de desarrollo es Windows.
- No se administrará Linux directamente.
- Laravel será el framework principal.
- MySQL será la base de datos.
- Blade será utilizado para el frontend del MVP, con Tailwind CSS v4.
- La autenticación se genera con Laravel Breeze (stack Blade); el registro público se elimina y los usuarios los crea el Administrador.
- La arquitectura será inicialmente MVC estándar de Laravel.
- Las decisiones técnicas de la Fase 3 están consolidadas en `docs/Decisiones_Tecnicas_JP.md` (costeo promedio ponderado móvil, soft-delete, dos ledgers, saldo a favor, crédito por venta, etc.).
- No se construirá una API REST en el MVP.
- No se utilizará Docker.
- El inventario será un stock único y global, sin gestión de inventario por ubicación.
- El sistema manejará variantes de productos por talla y color.
- Las ventas conservarán el precio real de la transacción.
- El inventario disminuirá automáticamente al confirmar una venta.
- Las operaciones críticas de inventario y ventas deben garantizar integridad ante concurrencia.
- Las operaciones monetarias utilizarán tipos apropiados para dinero y nunca `float`.
- Los feature tests mínimos forman parte del MVP.
- Las funcionalidades V2 deben mantenerse separadas del MVP.
- No se debe agregar complejidad técnica sin justificación.

---

# ROLES

El sistema tendrá inicialmente dos roles:

## Administrador

Tiene control general del sistema.

Puede:

- Gestionar productos.
- Gestionar variantes.
- Gestionar precios de referencia.
- Registrar entradas de inventario.
- Realizar ajustes de inventario.
- Gestionar clientes.
- Gestionar crédito y abonos.
- Consultar reportes.
- Gestionar usuarios.

## Empleado / Vendedor

Puede:

- Buscar productos.
- Consultar stock.
- Registrar ventas.
- Registrar el precio real permitido por las reglas del negocio.
- Utilizar el módulo de ventas.

No puede acceder a funciones administrativas ni reportes financieros.

Toda venta debe quedar asociada al usuario que la registró.

---

# REGLAS DE NEGOCIO IMPORTANTES

Claude Code debe respetar las reglas definidas en el documento oficial de requisitos (`docs/Documento_Requisitos_Software_JP.md`, ver sección 7 — RN-01 a RN-15).

Entre las más importantes:

- El inventario es global.
- Una venta confirmada descuenta stock.
- Cada venta conserva su precio real.
- La ganancia depende del precio real y del costo correspondiente a la unidad vendida.
- Los cambios de costo aplican a nuevas compras y no deben alterar retroactivamente el costo histórico.
- Un cliente en mora tiene restricciones para nuevas compras a crédito según las reglas definidas.
- Una devolución válida genera saldo a favor y no reembolso en efectivo.
- La anulación (antes de la entrega) y la devolución (después de la entrega) son procesos distintos — no deben tratarse como lo mismo.
- El administrador decide cómo tratar productos dañados.
- Los umbrales de stock bajo pueden variar por producto.
- El historial de modificaciones de producto debe mantenerse separado del historial de ventas.

NO inventes reglas de negocio que no estén definidas en los documentos o aprobadas explícitamente por el usuario.

---

# DECISIONES TÉCNICAS QUE DEBEN RESOLVERSE ANTES DE CIERTOS MÓDULOS

Hay algunas decisiones técnicas importantes que no deben improvisarse durante la implementación:

1. Estrategia de costeo del inventario.
2. Manejo del costo histórico por unidad.
3. Integridad transaccional ante ventas simultáneas.
4. Estrategia de borrado o conservación de productos con historial.
5. Estructura del historial de modificaciones.
6. Estructura de productos y variantes.
7. Manejo correcto de saldo a favor.
8. Integridad de las operaciones de crédito.

Estas decisiones deben documentarse antes de implementarlas.

Nunca elijas una solución técnica compleja solo porque sea más sofisticada.

---

# MVP

El MVP incluye, de acuerdo con los requisitos:

- Autenticación.
- Usuarios y roles.
- Productos.
- Variantes por talla y color.
- Inventario.
- Entradas de mercancía.
- Ajustes de inventario.
- Alertas visuales de stock bajo.
- Ventas.
- Precio real de venta.
- Descuentos.
- Métodos de pago.
- Anulación antes de la entrega.
- Devoluciones.
- Saldo a favor.
- Clientes.
- Crédito.
- Abonos.
- Control de mora.
- Historial de producto.
- Reportes.
- Dashboard.

No extiendas el MVP con funcionalidades V2 sin autorización explícita.

---

# V2 Y FUTURO

Considera como posteriores:

- Gestión completa de proveedores.
- Gestión completa de compras.
- Reporte de productos sin movimiento.
- Límite máximo de crédito configurable.
- App móvil.
- Comercio electrónico.
- Código de barras.
- Facturación electrónica.
- Múltiples sucursales.
- Automatizaciones externas más avanzadas.
- Branding profesional completo.

No implementar estas funcionalidades por iniciativa propia.

---

# DOCUMENTOS DE REFERENCIA

Los documentos originales del proyecto son la fuente detallada del contexto y deben vivir dentro del repositorio, por ejemplo en una carpeta `docs/`:

- `docs/Documento_Requisitos_Software_JP.md` — documento oficial de requisitos (información general, problema actual, objetivos, alcance, actores y roles, procesos, reglas de negocio RN-01 a RN-15, requisitos funcionales RF-001 a RF-024, requisitos no funcionales, diseño e identidad visual, fuera de alcance, pendientes, matriz de trazabilidad, criterios de aceptación, glosario).
- `docs/Plan_Proyecto_JP.md` — plan de proyecto y estudio de 8 semanas (fases, decisiones técnicas consolidadas, cronograma, sprints del MVP).

Cuando necesites información específica sobre requisitos funcionales, reglas de negocio, actores, alcance, requisitos no funcionales, MVP, V2 o decisiones pendientes, consulta primero el documento correspondiente.

El `CLAUDE.md` contiene el contexto general y las reglas permanentes. Los documentos detallados contienen la especificación completa.

No dupliques en `CLAUDE.md` todos los requisitos detallados si una referencia al documento correspondiente es suficiente.

---

# METODOLOGÍA DE TRABAJO

Claude Code debe trabajar siguiendo este orden:

**Comprender → Analizar → Planificar → Implementar → Verificar → Documentar**

Antes de modificar código:

1. Comprende el requisito.
2. Revisa el código existente.
3. Identifica dependencias.
4. Determina el impacto.
5. Propón el enfoque.
6. Implementa.
7. Ejecuta pruebas.
8. Verifica que no se rompan funcionalidades existentes.

No empieces a programar inmediatamente ante una solicitud compleja. Primero entiende el contexto.

---

# REGLAS DE DESARROLLO

## No sobreingeniería

No introduzcas:

- Arquitecturas innecesariamente complejas.
- Microservicios.
- Capas innecesarias.
- Patrones innecesarios.
- Dependencias innecesarias.
- Librerías externas cuando Laravel ya proporciona una solución adecuada.

Prefiere la solución Laravel estándar más sencilla que satisfaga el requisito.

## Consistencia

Respeta:

- Convenciones existentes.
- Nombres existentes.
- Estructura del proyecto.
- Patrones ya adoptados.

No cambies estructuras globales sin necesidad.

## Seguridad

Nunca:

- Expongas credenciales.
- Versiones archivos `.env`.
- Introduzcas secretos directamente en código.
- Desactives protecciones de seguridad para "hacer funcionar" algo.
- Permitas acceso a funcionalidades sin respetar el rol.

## Base de datos

No modifiques el esquema arbitrariamente. Las decisiones estructurales importantes deben estar reflejadas en migraciones y documentación.

## Código

Prioriza:

- Claridad.
- Mantenibilidad.
- Validaciones.
- Integridad de datos.
- Responsabilidades bien definidas.
- Código fácil de entender para un desarrollador que está aprendiendo Laravel.

---

# MODO DE TRABAJO CON EL USUARIO

El usuario está utilizando este proyecto también para aprender.

Por lo tanto:

Cuando una tarea sea educativa, explica brevemente:

- Qué estamos haciendo.
- Por qué lo hacemos.
- Qué concepto de Laravel estamos aplicando.

No conviertas cada tarea en una clase teórica extensa.

Cuando el usuario simplemente pida implementar algo, sé directo.

Cuando exista una decisión importante que tenga varias alternativas, presenta las alternativas y recomienda una.

No ocultes decisiones técnicas relevantes. No inventes información.

---

# GESTIÓN DE TAREAS

No implementes varias funcionalidades grandes simultáneamente sin dividirlas.

Cuando una tarea sea suficientemente grande:

1. Divide el trabajo.
2. Identifica dependencias.
3. Implementa una parte.
4. Verifica.
5. Continúa con la siguiente.

Cada cambio importante debe poder verificarse.

---

# TESTING

Las pruebas son parte del proyecto, no una tarea opcional.

Prioriza feature tests para:

- Autenticación.
- Roles.
- Ventas.
- Inventario.
- Crédito.
- Devoluciones.
- Reglas críticas.

Antes de considerar terminada una funcionalidad importante, verifica su comportamiento.

---

# DOCUMENTACIÓN

Mantén actualizada la documentación importante cuando una decisión cambie.

No documentes innecesariamente cada línea de código.

Documenta especialmente:

- Decisiones arquitectónicas.
- Reglas de negocio.
- Decisiones técnicas importantes.
- Comandos importantes.
- Configuración especial.
- Cambios que puedan afectar futuras fases.

---

# CONTROL DEL ALCANCE

El proyecto tiene una duración objetivo de aproximadamente 8 semanas.

Prioriza siempre el MVP.

Si una solicitud del usuario puede aumentar considerablemente el alcance, indícalo antes de implementarla.

No agregues funcionalidades "porque podrían ser útiles".

Pregunta conceptualmente:

**¿Esto resuelve una necesidad del negocio, del MVP o del aprendizaje?**

Si la respuesta es no, probablemente no debe entrar al proyecto.

---

# REGLA FUNDAMENTAL

Este es un proyecto real de negocio y simultáneamente un proyecto de aprendizaje y portafolio.

Por eso:

**No queremos simplemente código que funcione.**

Queremos:

- Código comprensible.
- Reglas de negocio correctas.
- Datos íntegros.
- Arquitectura razonable.
- Pruebas.
- Documentación.
- Decisiones justificadas.
- Un proyecto que pueda explicarse profesionalmente en una entrevista.

Pero nunca sacrifiques la simplicidad por intentar impresionar.

---

# REGLA FINAL

Antes de implementar cualquier cambio importante, verifica:

1. ¿Está respaldado por un requisito?
2. ¿Respeta las reglas de negocio?
3. ¿Está dentro del alcance?
4. ¿Existe una decisión técnica pendiente?
5. ¿Puede introducir una regresión?
6. ¿Necesita una prueba?
7. ¿Necesita actualizar documentación?

Si alguna respuesta es relevante, considérala antes de modificar el proyecto.

El objetivo final es construir el sistema JP correctamente, paso a paso, aprendiendo Laravel durante el proceso y evitando decisiones improvisadas.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
