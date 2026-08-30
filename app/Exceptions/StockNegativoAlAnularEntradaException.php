<?php

namespace App\Exceptions;

use App\Models\EntradaInventario;
use RuntimeException;

/**
 * Se lanza cuando, al reproducir el ledger sin la entrada que se quiere anular,
 * el stock se vuelve negativo en algún punto (guarda A4.b): se vendió mercancía
 * que solo existía por la entrada mal capturada.
 *
 * La anulación se rechaza; el Administrador debe reconciliar primero con un
 * ajuste por conteo físico (RN-10) y luego anular. Provoca el `ROLLBACK`.
 */
class StockNegativoAlAnularEntradaException extends RuntimeException
{
    public static function faltan(EntradaInventario $entrada, int $unidades): self
    {
        return new self(sprintf(
            'No se puede anular la entrada #%d: al descontarla el stock quedaría corto en %d %s. '.
            'Registra primero un ajuste por conteo físico y vuelve a intentarlo.',
            $entrada->id,
            $unidades,
            $unidades === 1 ? 'unidad' : 'unidades',
        ));
    }
}
