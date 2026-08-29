<?php

namespace App\Http\Controllers;

use App\Enums\EstadoDevolucion;
use App\Enums\EstadoVenta;
use App\Exceptions\DevolucionInvalidaException;
use App\Http\Requests\StoreDevolucionRequest;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\Devoluciones\RegistrarDevolucion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Devoluciones de mercancía ya entregada (RF-011; flujo 4.4). Solo Administrador
 * — protegido por el middleware `rol:administrador` en las rutas.
 *
 * La lógica crítica (transacción + bloqueo + reintegro por línea + generación de
 * saldo a favor) vive en {@see RegistrarDevolucion}.
 */
class DevolucionController extends Controller
{
    public function index(): View
    {
        $devoluciones = Devolucion::query()
            ->with(['venta.cliente', 'usuario'])
            ->latest('id')
            ->paginate(20);

        return view('admin.devoluciones.index', compact('devoluciones'));
    }

    public function create(Venta $venta): View
    {
        abort_unless($venta->entregada_at !== null && $venta->estado === EstadoVenta::Confirmada, 404);

        $venta->load('lineas.variante.producto');

        return view('admin.devoluciones.create', compact('venta'));
    }

    public function store(StoreDevolucionRequest $request, Venta $venta, RegistrarDevolucion $registrar): RedirectResponse
    {
        try {
            $devolucion = $registrar->ejecutar(
                $venta,
                $request->lineasParaServicio(),
                $request->validated('motivo'),
                $request->estadoParaServicio(),
                Carbon::parse($request->validated('fecha')),
                $request->user(),
            );
        } catch (DevolucionInvalidaException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $mensaje = $devolucion->estado === EstadoDevolucion::Validada
            ? "Devolución registrada. Saldo a favor generado: {$devolucion->saldo_generado}."
            : 'Devolución registrada como rechazada.';

        return redirect()->route('ventas.show', $venta)->with('status', $mensaje);
    }
}
