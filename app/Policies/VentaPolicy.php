<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;

/**
 * Autorización de ventas (decisiones funcionales G1–G3).
 *
 * - Empleado y Administrador pueden registrar ventas y verlas.
 * - El Empleado solo ve y opera **sus** ventas (RN-08); el Administrador, todas.
 * - Anular: solo si la venta sigue siendo anulable (confirmada y no entregada,
 *   RF-010) y además es propia — salvo el Administrador, que puede cualquiera.
 * - Marcar entregada (G3): una venta confirmada y no entregada, propia o
 *   cualquiera si es Administrador.
 *
 * No se usa `before()` para el Administrador: la guarda de estado
 * (`esAnulable`, `puedeEntregarse`) debe aplicarle también.
 */
class VentaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Venta $venta): bool
    {
        return $this->esPropiaOEsAdmin($user, $venta);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function anular(User $user, Venta $venta): bool
    {
        return $venta->esAnulable() && $this->esPropiaOEsAdmin($user, $venta);
    }

    public function entregar(User $user, Venta $venta): bool
    {
        return $venta->puedeEntregarse() && $this->esPropiaOEsAdmin($user, $venta);
    }

    private function esPropiaOEsAdmin(User $user, Venta $venta): bool
    {
        return $user->esAdministrador() || $venta->usuario_id === $user->id;
    }
}
