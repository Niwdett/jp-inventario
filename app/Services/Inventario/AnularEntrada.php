<?php

namespace App\Services\Inventario;

use App\Enums\TipoMovimiento;
use App\Exceptions\EntradaNoAnulableException;
use App\Exceptions\StockNegativoAlAnularEntradaException;
use App\Models\EntradaInventario;
use App\Models\User;
use App\Models\Variante;
use Illuminate\Support\Facades\DB;

/**
 * Anula una entrada de mercancía mal capturada (decisión A4).
 *
 * En una transacción con la entrada y la fila de la variante bloqueadas
 * (`lockForUpdate`, bloque B1):
 *
 * 1. Revalidar que la entrada sigue siendo anulable (guarda A4.a: no hay doble
 *    anulación).
 * 2. Marcar la entrada como anulada con su auditoría (`anulada_at`,
 *    `anulada_por`, `motivo_anulacion`) — la fila nunca se borra.
 * 3. Reconstruir `stock` y `costo_promedio` reproduciendo el libro de
 *    movimientos de la variante desde cero ({@see ReconstruirCostoVariante});
 *    nunca se "deshace" el promedio con aritmética inversa.
 * 4. Si en algún punto de la reproducción el stock se vuelve negativo, se
 *    rechaza (guarda A4.b): hay que reconciliar antes con un ajuste físico.
 * 5. Anexar el movimiento compensatorio `anulacion_entrada` (append-only, A4.1);
 *    a diferencia del ajuste, sí registra el usuario (RN-15).
 *
 * Las líneas de venta (`venta_lineas`) no se tocan: RN-05 intacto, ganancias
 * históricas inmutables (A2). Solo cambian las ventas futuras y la valoración
 * de inventario en vivo (RF-018).
 */
class AnularEntrada
{
    public function __construct(
        private readonly ReconstruirCostoVariante $reconstruir,
    ) {}

    /**
     * @throws EntradaNoAnulableException
     * @throws StockNegativoAlAnularEntradaException
     */
    public function ejecutar(EntradaInventario $entrada, string $motivo, User $usuario): ResultadoAnulacionEntrada
    {
        return DB::transaction(function () use ($entrada, $motivo, $usuario) {
            /** @var EntradaInventario $entrada */
            $entrada = EntradaInventario::whereKey($entrada->getKey())->lockForUpdate()->firstOrFail();

            if (! $entrada->esAnulable()) {
                throw EntradaNoAnulableException::yaAnulada($entrada);
            }

            /** @var Variante $variante */
            $variante = Variante::withTrashed()->whereKey($entrada->variante_id)->lockForUpdate()->firstOrFail();

            $costoAnterior = (string) $variante->costo_promedio;
            $stockAnterior = $variante->stock;

            // Se marca anulada ANTES de reproducir: la reconstrucción la ignora
            // a partir de `anulada_at`.
            $entrada->anulada_at = now();
            $entrada->anulada_por = $usuario->id;
            $entrada->motivo_anulacion = $motivo;
            $entrada->save();

            $reconstruido = $this->reconstruir->calcular($variante);

            if ($reconstruido['faltante'] > 0) {
                // Revierte todo, incluida la marca de anulación.
                throw StockNegativoAlAnularEntradaException::faltan($entrada, $reconstruido['faltante']);
            }

            // Asignación directa: `stock` y `costo_promedio` no son fillable.
            $variante->stock = $reconstruido['stock'];
            $variante->costo_promedio = $reconstruido['costo_promedio'];
            $variante->save();

            $entrada->movimientos()->create([
                'variante_id' => $variante->id,
                'tipo' => TipoMovimiento::AnulacionEntrada,
                'cantidad' => -$entrada->cantidad,
                'stock_resultante' => $reconstruido['stock'],
                'usuario_id' => $usuario->id,
            ]);

            return new ResultadoAnulacionEntrada(
                entrada: $entrada,
                etiquetaVariante: $variante->etiqueta(),
                costoAnterior: $costoAnterior,
                costoNuevo: $reconstruido['costo_promedio'],
                stockAnterior: $stockAnterior,
                stockNuevo: $reconstruido['stock'],
            );
        });
    }
}
