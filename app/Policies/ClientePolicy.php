<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * Autorización de clientes (RF-013, decisión G1 revisada 2026-08-29).
 *
 * El Empleado puede **crear, consultar y editar** clientes (los necesita en el
 * mostrador para no duplicarlos y para verificar el saldo antes de una venta).
 * **Eliminar y restaurar** siguen siendo exclusivos del Administrador: borran de
 * la vista un registro con historial de dinero.
 */
class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return true;
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->esAdministrador();
    }

    public function restore(User $user, Cliente $cliente): bool
    {
        return $user->esAdministrador();
    }
}
