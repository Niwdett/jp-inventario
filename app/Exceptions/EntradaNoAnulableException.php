<?php

namespace App\Exceptions;

use App\Models\EntradaInventario;
use RuntimeException;

/**
 * Se lanza al intentar anular una entrada que ya está anulada (guarda A4.a: no
 * hay doble anulación). Provoca el `ROLLBACK` de la transacción.
 */
class EntradaNoAnulableException extends RuntimeException
{
    public static function yaAnulada(EntradaInventario $entrada): self
    {
        return new self(sprintf(
            'La entrada #%d ya fue anulada el %s.',
            $entrada->id,
            $entrada->anulada_at?->format('Y-m-d H:i') ?? 'anteriormente',
        ));
    }
}
