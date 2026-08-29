<?php

namespace App\Enums;

/**
 * Estado de una devolución (`devoluciones`, RF-011; flujo 4.4).
 *
 * - `Validada`: el Administrador la aceptó; generó saldo a favor y, por línea,
 *   pudo reintegrar stock.
 * - `Rechazada`: se registra para auditoría; no genera saldo ni toca stock.
 */
enum EstadoDevolucion: string
{
    case Validada = 'validada';
    case Rechazada = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Validada => 'Validada',
            self::Rechazada => 'Rechazada',
        };
    }
}
