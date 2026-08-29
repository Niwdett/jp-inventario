<?php

namespace App\Enums;

/**
 * Tipos de movimiento del libro de saldo a favor (`saldo_favor_movimientos`,
 * bloque C1).
 *
 * - `Generado`: una devolución válida abona saldo al cliente (monto +).
 * - `Aplicado`: una venta posterior usa el saldo como medio de pago (monto −),
 *   RF-012.
 */
enum TipoSaldoFavor: string
{
    case Generado = 'generado';
    case Aplicado = 'aplicado';

    public function label(): string
    {
        return match ($this) {
            self::Generado => 'Saldo generado',
            self::Aplicado => 'Saldo aplicado',
        };
    }
}
