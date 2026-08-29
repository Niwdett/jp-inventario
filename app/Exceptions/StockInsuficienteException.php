<?php

namespace App\Exceptions;

use App\Models\Variante;
use RuntimeException;

/**
 * Se lanza cuando una operación de venta intenta descontar más unidades de las
 * que hay en stock. Provoca el `ROLLBACK` de la transacción completa (bloque B1:
 * el stock nunca puede quedar negativo).
 */
class StockInsuficienteException extends RuntimeException
{
    public static function paraVariante(Variante $variante, int $solicitado, int $disponible): self
    {
        return new self(sprintf(
            'Stock insuficiente para "%s": se pidieron %d y hay %d disponibles.',
            $variante->etiqueta(),
            $solicitado,
            $disponible,
        ));
    }
}
