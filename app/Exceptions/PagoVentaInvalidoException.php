<?php

namespace App\Exceptions;

use App\Http\Requests\StoreVentaRequest;
use RuntimeException;

/**
 * Se lanza cuando la composición del pago de una venta es incoherente: falta el
 * cliente obligatorio (E1) o se intenta aplicar más saldo a favor que el total
 * de la venta (RN-12). Provoca el `ROLLBACK` de la transacción completa.
 *
 * El {@see StoreVentaRequest} ya bloquea estos casos antes de
 * llegar aquí; esta excepción es la red de seguridad del servicio.
 */
class PagoVentaInvalidoException extends RuntimeException
{
    public static function clienteRequerido(): self
    {
        return new self('Una venta a crédito o que aplica saldo a favor debe tener un cliente asociado.');
    }

    public static function saldoExcedeTotal(string $saldo, string $total): self
    {
        return new self(sprintf(
            'El saldo a favor a aplicar (%s) no puede superar el total de la venta (%s).',
            $saldo,
            $total,
        ));
    }
}
