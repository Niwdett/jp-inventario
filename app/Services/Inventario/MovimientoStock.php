<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\User;
use App\Models\Variante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Movimiento de stock reutilizable por venta, anulación y (Sprint 4) devolución.
 *
 * **Contrato:** el llamador ya abrió una transacción y ya bloqueó la fila de la
 * variante (`lockForUpdate`), en orden de `id` para evitar deadlocks (bloque B1).
 * Este helper solo aplica el cambio de `stock`, mantiene el modelo en memoria
 * coherente y anexa el renglón al libro `movimientos_inventario`.
 *
 * `RegistrarEntrada` y `AjustarInventario` **no** usan este helper: sus
 * semánticas (recalcular el costo promedio, fijar el stock a un valor absoluto)
 * son distintas de "descontar" / "reintegrar".
 */
class MovimientoStock
{
    /**
     * Descuenta unidades del stock con guarda de no-negatividad (bloque B1).
     *
     * El `UPDATE` lleva la condición `stock >= :cantidad` y se exige que afecte
     * exactamente 1 fila; si no, el stock no alcanza y se lanza
     * {@see StockInsuficienteException}, lo que revierte toda la transacción.
     */
    public function descontar(
        Variante $variante,
        int $cantidad,
        TipoMovimiento $tipo,
        Model $referencia,
        ?User $usuario,
    ): MovimientoInventario {
        $afectadas = DB::table('variantes')
            ->where('id', $variante->id)
            ->where('stock', '>=', $cantidad)
            ->decrement('stock', $cantidad, ['updated_at' => now()]);

        if ($afectadas !== 1) {
            $disponible = (int) DB::table('variantes')->where('id', $variante->id)->value('stock');

            throw StockInsuficienteException::paraVariante($variante, $cantidad, $disponible);
        }

        $stockResultante = $variante->stock - $cantidad;
        $variante->stock = $stockResultante;

        return $this->registrarMovimiento($variante, -$cantidad, $stockResultante, $tipo, $referencia, $usuario);
    }

    /**
     * Reintegra unidades al stock (anulación, devolución). Sumar nunca deja el
     * stock negativo, así que no necesita guarda.
     */
    public function reintegrar(
        Variante $variante,
        int $cantidad,
        TipoMovimiento $tipo,
        Model $referencia,
        ?User $usuario,
    ): MovimientoInventario {
        DB::table('variantes')
            ->where('id', $variante->id)
            ->increment('stock', $cantidad, ['updated_at' => now()]);

        $stockResultante = $variante->stock + $cantidad;
        $variante->stock = $stockResultante;

        return $this->registrarMovimiento($variante, $cantidad, $stockResultante, $tipo, $referencia, $usuario);
    }

    private function registrarMovimiento(
        Variante $variante,
        int $cantidadConSigno,
        int $stockResultante,
        TipoMovimiento $tipo,
        Model $referencia,
        ?User $usuario,
    ): MovimientoInventario {
        return MovimientoInventario::create([
            'variante_id' => $variante->id,
            'tipo' => $tipo,
            'cantidad' => $cantidadConSigno,
            'stock_resultante' => $stockResultante,
            'referencia_type' => $referencia->getMorphClass(),
            'referencia_id' => $referencia->getKey(),
            'usuario_id' => $usuario?->id,
        ]);
    }
}
