<?php

namespace App\Exceptions;

use App\Models\Cliente;
use RuntimeException;

/**
 * Se lanza cuando se intenta confirmar una venta a crédito a un cliente en mora
 * (RN-09 / RF-015) sin la autorización necesaria. Provoca el `ROLLBACK` de la
 * transacción completa.
 *
 * - Un Empleado nunca puede forzarla.
 * - Un Administrador sí, pero debe autorizarla explícitamente; entonces se
 *   registra en `ventas.credito_autorizado_por`.
 */
class ClienteEnMoraException extends RuntimeException
{
    public static function empleado(Cliente $cliente): self
    {
        return new self(sprintf(
            'El cliente "%s" está en mora: solo un Administrador puede autorizar una nueva venta a crédito.',
            $cliente->nombre,
        ));
    }

    public static function requiereAutorizacion(Cliente $cliente): self
    {
        return new self(sprintf(
            'El cliente "%s" está en mora. Marca la autorización para registrar la venta a crédito de todos modos.',
            $cliente->nombre,
        ));
    }
}
