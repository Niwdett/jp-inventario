<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a uno o más roles (RF-001, decisión técnica F2).
 *
 * Uso en rutas:  ->middleware('rol:administrador')
 *                ->middleware('rol:administrador,empleado')
 *
 * Se asume que la ruta también pasa por el middleware `auth`; este middleware
 * solo decide si el usuario ya autenticado tiene un rol permitido.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if (! in_array($user->rol->value, $roles, strict: true)) {
            abort(403);
        }

        return $next($request);
    }
}
