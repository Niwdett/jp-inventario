<?php

namespace App\Exceptions;

use App\Models\Cliente;
use RuntimeException;

/**
 * Se lanza cuando una venta intenta aplicar más saldo a favor del que el cliente
 * tiene disponible. Provoca el `ROLLBACK` de la transacción completa (bloque C1:
 * `clientes.saldo_favor` nunca puede quedar negativo).
 */
class SaldoFavorInsuficienteException extends RuntimeException
{
    public static function paraCliente(Cliente $cliente, string $solicitado, string $disponible): self
    {
        return new self(sprintf(
            'Saldo a favor insuficiente para "%s": se quiso aplicar %s y hay %s disponibles.',
            $cliente->nombre,
            $solicitado,
            $disponible,
        ));
    }
}
