<?php

namespace App\Exceptions;

use App\Models\Venta;
use RuntimeException;

/**
 * Se lanza cuando un abono no puede aplicarse: la venta no es a crédito, ya está
 * anulada, o el monto supera el saldo pendiente (sin sobrepago, C2). Provoca el
 * `ROLLBACK` de la transacción completa.
 */
class AbonoInvalidoException extends RuntimeException
{
    public static function ventaNoAplica(Venta $venta): self
    {
        return new self(sprintf('La venta %s no admite abonos (no es a crédito o está anulada).', $venta->numero));
    }

    public static function sobrepago(Venta $venta, string $monto, string $pendiente): self
    {
        return new self(sprintf(
            'El abono (%s) supera el saldo pendiente de la venta %s (%s).',
            $monto,
            $venta->numero,
            $pendiente,
        ));
    }
}
