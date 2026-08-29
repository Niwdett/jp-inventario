<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\VentaNoAnulableException;
use App\Http\Requests\AnularVentaRequest;
use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Variante;
use App\Models\Venta;
use App\Policies\VentaPolicy;
use App\Services\Ventas\AnularVenta;
use App\Services\Ventas\RegistrarVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ventas (RF-008, RF-009, RF-010). Accesible para Empleado y Administrador; la
 * {@see VentaPolicy} decide el detalle: el Empleado solo ve y
 * opera sus propias ventas (RN-08).
 *
 * La lógica crítica (transacción + bloqueo + descuento de stock con guarda de
 * no-negatividad) vive en los servicios `RegistrarVenta` y `AnularVenta`.
 */
class VentaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Venta::class);

        $ventas = Venta::with(['cliente', 'usuario'])
            ->when(
                $request->user()->esEmpleado(),
                fn ($query) => $query->where('usuario_id', $request->user()->id),
            )
            ->when(
                in_array($request->query('estado'), ['confirmada', 'anulada'], true),
                fn ($query) => $query->where('estado', $request->query('estado')),
            )
            ->latest('fecha_venta')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('ventas.index', compact('ventas'));
    }

    public function create(): View
    {
        $this->authorize('create', Venta::class);

        return view('ventas.create', [
            'variantes' => Variante::opcionesParaSelect(),
            'metodosPago' => MetodoPago::disponiblesEnContado(),
        ]);
    }

    public function store(StoreVentaRequest $request, RegistrarVenta $registrar): RedirectResponse
    {
        $cliente = $request->filled('cliente_id')
            ? Cliente::find($request->validated('cliente_id'))
            : null;

        try {
            $venta = $registrar->ejecutar(
                lineas: $request->lineasParaServicio(),
                metodoPago: MetodoPago::from($request->validated('metodo_pago')),
                cliente: $cliente,
                usuario: $request->user(),
            );
        } catch (StockInsuficienteException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Venta {$venta->numero} registrada.");
    }

    public function show(Venta $venta): View
    {
        $this->authorize('view', $venta);

        $venta->load(['lineas.variante.producto', 'usuario', 'anuladaPor', 'cliente']);

        return view('ventas.show', compact('venta'));
    }

    public function anular(AnularVentaRequest $request, Venta $venta, AnularVenta $anular): RedirectResponse
    {
        try {
            $anular->ejecutar($venta, $request->validated('motivo'), $request->user());
        } catch (VentaNoAnulableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Venta {$venta->numero} anulada. El stock fue reintegrado.");
    }

    public function entregar(Venta $venta): RedirectResponse
    {
        $this->authorize('entregar', $venta);

        $venta->forceFill(['entregada_at' => now()])->save();

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Venta {$venta->numero} marcada como entregada.");
    }
}
