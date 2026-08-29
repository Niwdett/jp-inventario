<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Models\EntradaInventario;
use App\Models\User;
use App\Models\Variante;
use Illuminate\Support\Facades\DB;

/**
 * Registra una entrada de mercancía (RF-005) y recalcula el costo promedio
 * ponderado móvil de la variante (A1).
 *
 * Todo ocurre en una transacción con la fila de la variante bloqueada
 * (`lockForUpdate`, bloque B1): actualizar `stock` + `costo_promedio`, insertar
 * la entrada y anexar el renglón al libro `movimientos_inventario`.
 *
 * El cálculo del promedio usa aritmética decimal (BCMath), nunca `float`.
 */
class RegistrarEntrada
{
    public function ejecutar(
        Variante $variante,
        int $cantidad,
        string $costoUnitario,
        string $fecha,
        ?string $proveedor,
        User $usuario,
    ): EntradaInventario {
        return DB::transaction(function () use ($variante, $cantidad, $costoUnitario, $fecha, $proveedor, $usuario) {
            /** @var Variante $variante */
            $variante = Variante::whereKey($variante->getKey())->lockForUpdate()->firstOrFail();

            $stockAnterior = $variante->stock;
            $nuevoStock = $stockAnterior + $cantidad;
            $nuevoCosto = $this->promedioPonderado(
                $stockAnterior,
                (string) $variante->costo_promedio,
                $cantidad,
                $costoUnitario,
            );

            // Asignación directa: `stock` y `costo_promedio` no son fillable
            // (el CRUD de variantes no debe tocarlos); solo cambian por aquí.
            $variante->stock = $nuevoStock;
            $variante->costo_promedio = $nuevoCosto;
            $variante->save();

            $entrada = EntradaInventario::create([
                'variante_id' => $variante->id,
                'usuario_id' => $usuario->id,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'fecha' => $fecha,
                'proveedor' => $proveedor,
            ]);

            $entrada->movimientos()->create([
                'variante_id' => $variante->id,
                'tipo' => TipoMovimiento::Entrada,
                'cantidad' => $cantidad,
                'stock_resultante' => $nuevoStock,
                'usuario_id' => $usuario->id,
            ]);

            return $entrada;
        });
    }

    /**
     * costo_promedio_nuevo =
     *   (stock_actual · costo_actual + cantidad_entrada · costo_entrada)
     *   / (stock_actual + cantidad_entrada)
     */
    private function promedioPonderado(int $stockActual, string $costoActual, int $cantidadEntrada, string $costoEntrada): string
    {
        $valorActual = bcmul((string) $stockActual, $costoActual, 6);
        $valorEntrada = bcmul((string) $cantidadEntrada, $costoEntrada, 6);
        $total = bcadd($valorActual, $valorEntrada, 6);

        $promedio = bcdiv($total, (string) ($stockActual + $cantidadEntrada), 6);

        // `costo_promedio` es decimal(12,4): se redondea (no se trunca) a 4 decimales.
        return bcround($promedio, 4);
    }
}
