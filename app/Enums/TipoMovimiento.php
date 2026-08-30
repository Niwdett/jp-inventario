<?php

namespace App\Enums;

/**
 * Tipos de movimiento del libro de inventario (`movimientos_inventario`, bloque B3b).
 *
 * `entrada` y `ajuste` se implementan en Sprint 2; `venta`, `anulacion` y
 * `devolucion` en Sprints 3–4; `anulacion_entrada` en la decisión A4.
 */
enum TipoMovimiento: string
{
    case Entrada = 'entrada';
    case Venta = 'venta';
    case Anulacion = 'anulacion';
    case Devolucion = 'devolucion';
    case Ajuste = 'ajuste';
    case AnulacionEntrada = 'anulacion_entrada';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada de mercancía',
            self::Venta => 'Venta',
            self::Anulacion => 'Anulación de venta',
            self::Devolucion => 'Devolución',
            self::Ajuste => 'Ajuste manual',
            self::AnulacionEntrada => 'Anulación de entrada',
        };
    }
}
