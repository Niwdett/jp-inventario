# Sprint 1 — Autenticación, usuarios y roles

**Fase 7 del plan · Duración objetivo: 2 días · Cubre RF-001, RF-002 (y base de RN-08).**

> **Definición de "hecho":** un usuario con rol definido inicia sesión y ve solo
> lo que su rol permite.

---

## 1. Qué se construyó

| Área | Detalle |
|------|---------|
| Scaffolding de auth | Laravel **Breeze** (stack Blade) — decisión F2. Login, logout, recuperación de contraseña, confirmación de contraseña. |
| Registro público | **Eliminado.** No hay ruta `register` ni verificación de correo: las cuentas las crea el Administrador y nacen activas. |
| Perfil | Recortado a **cambiar la propia contraseña**. No se puede editar nombre/correo/rol ni auto-eliminarse. |
| Roles | Columna `users.rol` + enum PHP `App\Enums\Rol` (`administrador`, `empleado`). Sin tabla `roles` ni paquete de permisos. |
| Autorización | Middleware `App\Http\Middleware\EnsureRole`, alias `rol` en `bootstrap/app.php`. |
| CRUD de usuarios | `UsuarioController` (resource, sin `show`) bajo `/admin/usuarios`, solo Administrador. Form Requests `StoreUsuarioRequest` / `UpdateUsuarioRequest`. |
| Baja de usuarios | **Soft-delete** ("desactivar"): la cuenta deja de poder entrar pero se conservan sus ventas (RN-08). Acción "Reactivar" para revertir. |
| Primer admin | Comando `php artisan jp:crear-admin` (interactivo o con `--name/--email/--password`) + `AdminUserSeeder` para desarrollo. |
| Frontend | Tailwind **v4** (CSS-first, `@import 'tailwindcss'`), `@tailwindcss/forms`, componentes Blade propios de Breeze + `<x-select-input>`. |

## 2. Reglas e invariantes implementadas

- **El sistema nunca se queda sin ningún Administrador activo.** Se protege en dos
  puntos: no puedes autodegradarte al último admin (`UpdateUsuarioRequest`) y no
  puedes desactivar tu propia cuenta (`UsuarioController@destroy`). No hay otro
  camino a "cero administradores".
- Toda ruta administrativa exige `auth` **y** `rol:administrador`. Un Empleado
  recibe **403**; un invitado es redirigido a `login`.
- Las contraseñas se cifran mediante el cast `hashed` del modelo `User` (nunca se
  llama a `Hash::make` a mano en el flujo de alta/edición).
- El correo se normaliza a minúsculas antes de validar y guardar.
- Un usuario desactivado (`deleted_at` no nulo) no supera `auth()->validate()`,
  por lo que no puede iniciar sesión.

## 3. Rutas nuevas

```
GET    /dashboard                      dashboard            auth
GET    /profile                        profile.edit         auth
GET    /admin/usuarios                 admin.usuarios.index  auth + rol:administrador
GET    /admin/usuarios/create          admin.usuarios.create
POST   /admin/usuarios                 admin.usuarios.store
GET    /admin/usuarios/{usuario}/edit  admin.usuarios.edit
PUT    /admin/usuarios/{usuario}       admin.usuarios.update
DELETE /admin/usuarios/{usuario}       admin.usuarios.destroy
PATCH  /admin/usuarios/{usuario}/restaurar  admin.usuarios.restore
```

## 4. Migraciones añadidas

| Migración | Cambio |
|-----------|--------|
| `add_rol_to_users_table` | `enum('rol', ['administrador','empleado'])` con default `empleado`. |
| `add_soft_deletes_to_users_table` | `deleted_at` en `users`. |

## 5. Cómo levantarlo desde cero

```bash
php artisan migrate:fresh
php artisan jp:crear-admin        # crea el primer Administrador (producción y local)
# — o, solo en desarrollo —
php artisan migrate:fresh --seed  # admin@jp.test / vendedor@jp.test, clave: password
npm run build
php artisan serve
```

## 6. Pruebas (Pest, 33 tests)

| Archivo | Cubre |
|---------|-------|
| `tests/Feature/Auth/AuthenticationTest.php` | login, logout, credenciales inválidas (Breeze) |
| `tests/Feature/Auth/PasswordResetTest.php`, `PasswordUpdateTest.php`, `PasswordConfirmationTest.php` | recuperación / cambio de contraseña (Breeze) |
| `tests/Feature/Auth/RoleAccessTest.php` | invitado→login, empleado→403, admin→200, middleware multi-rol |
| `tests/Feature/Admin/UsuarioManagementTest.php` | CRUD completo, validaciones, unicidad de correo, no degradar último admin, no autodesactivarse, desactivar/reactivar, bloqueo de login tras desactivar |
| `tests/Feature/Console/CrearAdminCommandTest.php` | comando interactivo y no interactivo, correo duplicado, contraseña corta |

## 7. Deuda técnica registrada para Sprint 2

- **Los feature tests corren en SQLite en memoria** (`phpunit.xml`), pero el
  diseño de Fase 3 usa DDL específico de MySQL (columna generada `activo` para
  los índices UNIQUE con soft-delete, bloque F1). Antes de implementar productos
  y variantes hay que **mover los tests a un esquema MySQL de pruebas** o los
  índices no se podrán testear.
- La unicidad de `users.email` es global (incluye usuarios desactivados): no se
  puede reutilizar el correo de una cuenta dada de baja. Aceptable para el MVP;
  si el negocio lo pide, se aplica el mismo patrón de columna generada.
