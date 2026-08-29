<?php

namespace App\Enums;

/**
 * Método de pago del restante de una venta (bloque C1). Un solo método por venta
 * (RF-008): no hay pagos partidos en el MVP. El saldo a favor se aplica aparte
 * (`ventas.saldo_favor_aplicado`); si cubre el total, el restante es 0 y la
 * venta se registra como `Efectivo`.
 *
 * `Credito` genera una deuda por venta (Sprint 4): ver `RegistrarVenta` y el
 * bloque C2.
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
}
