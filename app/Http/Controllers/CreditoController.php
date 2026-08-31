<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Exceptions\AbonoInvalidoException;
use App\Http\Requests\StoreAbonoRequest;
use App\Models\Venta;
use App\Policies\VentaPolicy;
use App\Services\Creditos\RegistrarAbono;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Créditos y abonos (RF-014; flujo 4.5).
 *
 * `index` (cartera de crédito) es solo del Administrador. `abonar` lo comparte el
 * Empleado para las ventas a crédito que él mismo registró (RN-08): la
 * {@see VentaPolicy::abonar()} decide.
 *
 * La deuda es "una por venta" (C2). La lógica crítica (transacción + bloqueo +
 * guarda de no sobrepago) vive en {@see RegistrarAbono}.
 */
class CreditoController extends Controller
{
    public function index(): View
    {
        $ventas = Venta::query()
            ->where('metodo_pago', MetodoPago::Credito)
            ->where('credito_saldo_pendiente', '>', 0)
            ->with('cliente')
            ->orderBy('fecha_venta')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.creditos.index', compact('ventas'));
    }

    public function abonar(StoreAbonoRequest $request, Venta $venta, RegistrarAbono $registrar): RedirectResponse
    {
        try {
            $registrar->ejecutar(
                $venta,
                $request->montoParaServicio(),
                Carbon::parse($request->validated('fecha')),
                $request->user(),
                $request->idempotencyKey(),
            );
        } catch (AbonoInvalidoException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Abono registrado en la venta {$venta->numero}.");
    }
}
