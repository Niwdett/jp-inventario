<?php

namespace App\Exceptions;

use App\Models\Venta;
use App\Models\VentaLinea;
use RuntimeException;

/**
 * Se lanza cuando una devolución no puede procesarse: la venta no está entregada
 * (el camino sería la anulación), una línea devuelve más unidades de las
 * vendidas, o se valida una devolución sin cliente al que abonar el saldo a
 * favor. Provoca el `ROLLBACK` de la transacción completa.
 */
class DevolucionInvalidaException extends RuntimeException
{
    public static function ventaNoEntregada(Venta $venta): self
    {
        return new self(sprintf(
            'La venta %s no está entregada: una venta no entregada se anula, no se devuelve.',
            $venta->numero,
        ));
    }

    public static function sinCliente(Venta $venta): self
    {
        return new self(sprintf(
            'La venta %s no tiene cliente: no hay a quién abonar el saldo a favor de la devolución.',
            $venta->numero,
        ));
    }

    public static function excedeCantidad(VentaLinea $linea, int $solicitado, int $disponible): self
    {
        return new self(sprintf(
            'No se pueden devolver %d unidades de "%s": solo quedan %d sin devolver.',
            $solicitado,
            $linea->variante->etiqueta(),
            $disponible,
        ));
    }

    public static function lineaAjena(Venta $venta): self
    {
        return new self(sprintf('Una de las líneas a devolver no pertenece a la venta %s.', $venta->numero));
    }
}
