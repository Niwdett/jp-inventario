<?php

namespace App\Exceptions;

use App\Models\Venta;
use RuntimeException;

/**
 * Se lanza al intentar marcar como entregada una venta que ya no lo admite
 * (anulada, o ya entregada).
 *
 * La Policy hace la comprobación amable antes; esta excepción cubre la
 * revalidación dentro de la transacción, con la venta bloqueada, por si otra
 * operación (una anulación simultánea) cambió el estado entre medias — cierra
 * el riesgo E-2 de la auditoría de cierre.
 */
class VentaNoEntregableException extends RuntimeException
{
    public static function para(Venta $venta): self
    {
        return new self("La venta {$venta->numero} no se puede marcar como entregada: fue anulada o ya estaba entregada.");
    }
}
