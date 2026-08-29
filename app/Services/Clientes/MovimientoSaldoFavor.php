<?php

namespace App\Services\Clientes;

use App\Enums\TipoSaldoFavor;
use App\Exceptions\SaldoFavorInsuficienteException;
use App\Models\Cliente;
use App\Models\SaldoFavorMovimiento;
use App\Services\Inventario\MovimientoStock;
use Illuminate\Database\Eloquent\Model;

/**
 * Movimiento del saldo a favor del cliente, reutilizable por la devolución (que
 * lo genera) y por la venta (que lo aplica como pago, RF-012). Espejo de
 * {@see MovimientoStock}.
 *
 * **Contrato:** el llamador ya abrió una transacción y ya bloqueó la fila del
 * cliente (`lockForUpdate`). Bajo ese lock, la secuencia obligatoria del bloque
 * C1 —leer saldo → validar → descontar → registrar— es segura hecha en PHP: no
 * hay escritura concurrente posible sobre esa fila. Este helper aplica el cambio
 * a `clientes.saldo_favor`, mantiene el modelo en memoria coherente y anexa el
 * renglón al libro `saldo_favor_movimientos` (ambos en la misma transacción).
 *
 * Los importes se manejan como string con aritmética decimal, nunca `float`.
 */
class MovimientoSaldoFavor
{
    /**
     * Abona saldo a favor al cliente (una devolución validada, o el reverso de
     * una venta anulada que había aplicado saldo). Sumar nunca deja el saldo
     * negativo, así que no necesita guarda.
     */
    public function generar(Cliente $cliente, string $monto, Model $referencia): SaldoFavorMovimiento
    {
        $cliente->saldo_favor = bcadd((string) $cliente->saldo_favor, $monto, 2);
        $cliente->save();

        return $this->registrar($cliente, TipoSaldoFavor::Generado, $monto, $referencia);
    }

    /**
     * Aplica saldo a favor como pago de una venta (RF-012). Guarda de
     * no-negatividad (C1): si el saldo disponible no cubre el monto se lanza
     * {@see SaldoFavorInsuficienteException}, lo que revierte toda la transacción.
     */
    public function aplicar(Cliente $cliente, string $monto, Model $referencia): SaldoFavorMovimiento
    {
        $disponible = (string) $cliente->saldo_favor;

        if (bccomp($disponible, $monto, 2) < 0) {
            throw SaldoFavorInsuficienteException::paraCliente($cliente, $monto, $disponible);
        }

        $cliente->saldo_favor = bcsub($disponible, $monto, 2);
        $cliente->save();

        return $this->registrar($cliente, TipoSaldoFavor::Aplicado, bcmul($monto, '-1', 2), $referencia);
    }

    private function registrar(Cliente $cliente, TipoSaldoFavor $tipo, string $monto, Model $referencia): SaldoFavorMovimiento
    {
        return SaldoFavorMovimiento::create([
            'cliente_id' => $cliente->id,
            'tipo' => $tipo,
            'monto' => $monto,
            'referencia_type' => $referencia->getMorphClass(),
            'referencia_id' => $referencia->getKey(),
        ]);
    }
}
