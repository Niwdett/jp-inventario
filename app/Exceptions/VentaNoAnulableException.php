<?php

namespace App\Exceptions;

use App\Models\Venta;
use RuntimeException;

/**
 * Se lanza al intentar anular una venta que ya no admite anulación: o bien ya
 * fue entregada (RF-010: el camino es la devolución) o ya estaba anulada.
 *
 * La Policy hace la comprobación amable antes de llegar al servicio; esta
 * excepción cubre la revalidación dentro de la transacción, con la venta
 * bloqueada, por si el estado cambió entre medias.
 */
class VentaNoAnulableException extends RuntimeException
{
    public static function para(Venta $venta): self
    {
        return new self("La venta {$venta->numero} no se puede anular: ya fue entregada o anulada.");
    }
}
