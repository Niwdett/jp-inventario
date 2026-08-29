<?php

namespace App\Enums;

/**
 * Estado de una venta (bloque E). Una venta se crea `Confirmada` y solo puede
 * pasar a `Anulada` si aún no se ha entregado (RF-010). Tras la entrega, el
 * camino es la devolución (RF-011), que no cambia este estado.
 */
enum EstadoVenta: string
{
    case Confirmada = 'confirmada';
    case Anulada = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Confirmada => 'Confirmada',
            self::Anulada => 'Anulada',
        };
    }
}
