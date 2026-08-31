<?php

namespace App\Services\Creditos;

use App\Enums\EstadoVenta;
use App\Exceptions\AbonoInvalidoException;
use App\Models\Abono;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registro de un abono a una venta a crédito (RF-014; flujo 4.5).
 *
 * Modelo "una deuda por venta" (C2): en una transacción con la fila `ventas`
 * bloqueada, se valida que el monto no supere el saldo pendiente (sin
 * sobrepago), se inserta el abono y se baja `credito_saldo_pendiente`. Cuando
 * llega a 0 la deuda queda saldada.
 *
 * Importes como string con aritmética decimal, nunca `float`.
 */
class RegistrarAbono
{
    /**
     * Si se pasa `$idempotencyKey` y ya existe un abono con esa clave, se
     * devuelve ese abono sin registrar otro (protege contra el doble envío del
     * formulario). El índice UNIQUE de `abonos.idempotency_key` cubre además dos
     * peticiones simultáneas.
     *
     * @throws AbonoInvalidoException
     */
    public function ejecutar(Venta $venta, string $monto, Carbon $fecha, User $usuario, ?string $idempotencyKey = null): Abono
    {
        if ($idempotencyKey !== null && $previo = Abono::where('idempotency_key', $idempotencyKey)->first()) {
            return $previo;
        }

        try {
            return $this->registrar($venta, $monto, $fecha, $usuario, $idempotencyKey);
        } catch (UniqueConstraintViolationException $e) {
            if ($idempotencyKey !== null) {
                return Abono::where('idempotency_key', $idempotencyKey)->firstOrFail();
            }

            throw $e;
        }
    }

    /**
     * @throws AbonoInvalidoException
     */
    private function registrar(Venta $venta, string $monto, Carbon $fecha, User $usuario, ?string $idempotencyKey): Abono
    {
        return DB::transaction(function () use ($venta, $monto, $fecha, $usuario, $idempotencyKey) {
            /** @var Venta $venta */
            $venta = Venta::whereKey($venta->getKey())->lockForUpdate()->firstOrFail();

            if (! $venta->esCredito() || $venta->estado === EstadoVenta::Anulada) {
                throw AbonoInvalidoException::ventaNoAplica($venta);
            }

            $pendiente = (string) $venta->credito_saldo_pendiente;

            if (bccomp($monto, $pendiente, 2) > 0) {
                throw AbonoInvalidoException::sobrepago($venta, $monto, $pendiente);
            }

            $abono = $venta->abonos()->make([
                'monto' => $monto,
                'fecha' => $fecha->toDateString(),
                'usuario_id' => $usuario->id,
            ]);
            $abono->idempotency_key = $idempotencyKey;
            $abono->save();

            $venta->credito_saldo_pendiente = bcsub($pendiente, $monto, 2);
            $venta->save();

            return $abono;
        });
    }
}
