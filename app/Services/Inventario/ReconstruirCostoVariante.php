<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Models\EntradaInventario;
use App\Models\Variante;

/**
 * Reproduce el libro `movimientos_inventario` de una variante **desde cero**, en
 * orden de `id`, para obtener el `stock` y el `costo_promedio` que deberían ser
 * (decisión A4.2). Es una operación de solo lectura: no escribe nada.
 *
 * La usa {@see AnularEntrada} para recalcular tras marcar una entrada como
 * anulada, y sirve además como base del futuro comando de reconciliación
 * stock ↔ ledger (pendiente 🟠 de la auditoría de cierre).
 *
 * Reglas por movimiento (A4.2):
 *
 * | Movimiento | Efecto |
 * |------------|--------|
 * | `entrada` de una entrada NO anulada | promedio ponderado móvil; `stock += cantidad` |
 * | `entrada` de una entrada anulada, y su `anulacion_entrada` | se ignoran ambos |
 * | `venta`, `anulacion`, `devolucion` | `stock += cantidad` (con signo); el costo no cambia |
 * | `ajuste` | `stock = stock_resultante` (valor absoluto contado); el costo no cambia |
 *
 * Aritmética decimal (BCMath), nunca `float`; `costo_promedio` a 4 decimales,
 * igual que {@see RegistrarEntrada}.
 */
class ReconstruirCostoVariante
{
    /**
     * `faltante` > 0 indica que en algún paso el stock se volvió negativo
     * (guarda A4.b): es el déficit acumulado en el punto más bajo, es decir
     * cuántas unidades habría que reconciliar con un ajuste físico antes de
     * poder anular. Si `faltante` > 0 el `stock`/`costo_promedio` devueltos no
     * son utilizables (la reconstrucción es inválida) y el llamador debe
     * rechazar la operación.
     *
     * @return array{stock: int, costo_promedio: string, faltante: int}
     */
    public function calcular(Variante $variante): array
    {
        $entradas = $variante->entradas()->get()->keyBy('id');
        $movimientos = $variante->movimientos()->orderBy('id')->get();

        $stock = 0;
        $costo = '0';
        $stockMinimo = 0;

        foreach ($movimientos as $movimiento) {
            match ($movimiento->tipo) {
                TipoMovimiento::AnulacionEntrada => null, // par de una entrada anulada: se ignora
                TipoMovimiento::Ajuste => $stock = $movimiento->stock_resultante,
                TipoMovimiento::Entrada => [$stock, $costo] = $this->aplicarEntrada(
                    $stock,
                    $costo,
                    $movimiento->cantidad,
                    $entradas->get($movimiento->referencia_id),
                ),
                default => $stock += $movimiento->cantidad, // venta, anulacion, devolucion
            };

            $stockMinimo = min($stockMinimo, $stock);
        }

        return [
            'stock' => $stock,
            'costo_promedio' => bcadd($costo, '0', 4),
            'faltante' => $stockMinimo < 0 ? -$stockMinimo : 0,
        ];
    }

    /**
     * Aporte de un movimiento `entrada` a la reproducción. Si la entrada que lo
     * originó está anulada (o no se encuentra), no aporta nada.
     *
     * @return array{0: int, 1: string} nuevo `[stock, costo]`
     */
    private function aplicarEntrada(int $stock, string $costo, int $cantidad, ?EntradaInventario $entrada): array
    {
        if ($entrada === null || $entrada->anulada_at !== null) {
            return [$stock, $costo];
        }

        return [
            $stock + $cantidad,
            $this->promedioPonderado($stock, $costo, $cantidad, (string) $entrada->costo_unitario),
        ];
    }

    /**
     * costo_nuevo = (stock · costo + cantidad · costo_entrada) / (stock + cantidad).
     *
     * Misma fórmula y redondeo que {@see RegistrarEntrada::promedioPonderado()};
     * se duplica a propósito: los dos servicios son independientes por diseño.
     *
     * Si en una reconstrucción ya inválida (stock negativo, guarda A4.b) el
     * divisor no fuera positivo, se conserva el costo previo: el resultado se
     * descartará de todos modos.
     */
    private function promedioPonderado(int $stock, string $costo, int $cantidad, string $costoEntrada): string
    {
        if ($stock + $cantidad <= 0) {
            return bcround($costo, 4);
        }

        $valorActual = bcmul((string) $stock, $costo, 6);
        $valorEntrada = bcmul((string) $cantidad, $costoEntrada, 6);
        $promedio = bcdiv(bcadd($valorActual, $valorEntrada, 6), (string) ($stock + $cantidad), 6);

        return bcround($promedio, 4);
    }
}
