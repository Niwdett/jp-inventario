<?php

namespace App\Enums;

/**
 * Método de pago de una venta (bloque C1). Un solo método por venta (RF-008):
 * no hay pagos partidos en el MVP.
 *
 * `Credito` existe en el esquema desde el Sprint 3, pero la venta a crédito
 * (deuda, mora, autorización) se implementa en el Sprint 4: hasta entonces el
 * formulario de venta solo ofrece `Efectivo` y `Transferencia`.
 */
enum MetodoPago: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Credito = 'credito';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Credito => 'Crédito',
        };
    }

    /**
     * Métodos que un vendedor puede elegir en el Sprint 3 (venta de contado).
     *
     * @return array<int, self>
     */
    public static function disponiblesEnContado(): array
    {
        return [self::Efectivo, self::Transferencia];
    }
}
