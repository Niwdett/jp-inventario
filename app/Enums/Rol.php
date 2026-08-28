<?php

namespace App\Enums;

/**
 * Roles del sistema (RF-001).
 *
 * El sistema tiene exactamente dos roles fijos. No hay tabla `roles` ni paquete
 * de permisos: la autorización se resuelve con esta columna + el middleware
 * `EnsureRole` (decisión técnica F2).
 */
enum Rol: string
{
    case Administrador = 'administrador';
    case Empleado = 'empleado';

    /**
     * Etiqueta legible para la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Empleado => 'Empleado / Vendedor',
        };
    }

    /**
     * Opciones para selects de formularios: ['administrador' => 'Administrador', ...].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $rol) => [$rol->value => $rol->label()])
            ->all();
    }
}
