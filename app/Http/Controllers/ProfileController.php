<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Muestra la página de perfil.
     *
     * En este sistema el perfil se limita a que el usuario cambie su propia
     * contraseña. El nombre, el correo y el rol solo los edita el Administrador
     * desde el módulo de usuarios (RF-002).
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }
}
