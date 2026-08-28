<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestión de usuarios del sistema (RF-002). Solo Administrador — protegido por
 * el middleware `rol:administrador` en las rutas.
 *
 * "Eliminar" un usuario es un soft-delete ("desactivar"): la cuenta deja de
 * poder iniciar sesión pero se conservan las ventas que registró (RN-08).
 */
class UsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = User::withTrashed()
            ->orderBy('name')
            ->get()
            ->sortBy(fn (User $u) => $u->trashed() ? 1 : 0)
            ->values();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('admin.usuarios.create', ['roles' => Rol::options()]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        // El cast `hashed` del modelo User se encarga de cifrar la contraseña.
        User::create($request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Rol::options(),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario->fill($request->safe()->except('password'));

        if ($request->filled('password')) {
            $usuario->password = $request->validated()['password'];
        }

        $usuario->save();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        // El sistema no puede quedarse sin administradores: eso solo podría pasar
        // si el último admin se desactiva a sí mismo o se autodegrada (bloqueado
        // en UpdateUsuarioRequest). Basta, por tanto, con impedir la autodesactivación.
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuario desactivado.');
    }

    public function restore(User $usuario): RedirectResponse
    {
        $usuario->restore();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuario reactivado.');
    }
}
