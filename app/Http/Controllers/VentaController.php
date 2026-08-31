<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Exceptions\ClienteEnMoraException;
use App\Exceptions\PagoVentaInvalidoException;
use App\Exceptions\SaldoFavorInsuficienteException;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\VentaNoAnulableException;
use App\Exceptions\VentaNoEntregableException;
use App\Http\Requests\AnularVentaRequest;
use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Variante;
use App\Models\Venta;
use App\Policies\VentaPolicy;
use App\Services\Ventas\AnularVenta;
use App\Services\Ventas\RegistrarVenta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $buscar = trim($filtros['buscar'] ?? '');

        $ventas = Venta::with(['cliente', 'usuario', 'lineas.variante.producto'])
            ->when(
                $request->user()->esEmpleado(),
                fn (Builder $query) => $query->where('usuario_id', $request->user()->id),
            )
            ->when(
                in_array($request->query('estado'), ['confirmada', 'anulada'], true),
                fn (Builder $query) => $query->where('estado', $request->query('estado')),
            )
            ->when(
                ! empty($filtros['desde']),
                fn (Builder $query) => $query->where('fecha_venta', '>=', Carbon::parse($filtros['desde'])->startOfDay()),
            )
            ->when(
                ! empty($filtros['hasta']),
                fn (Builder $query) => $query->where('fecha_venta', '<=', Carbon::parse($filtros['hasta'])->endOfDay()),
            )
            ->when(
                $buscar !== '',
                fn (Builder $query) => $query->where(function (Builder $query) use ($buscar) {
                    $query->where('numero', 'like', "%{$buscar}%")
                        ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->where('nombre', 'like', "%{$buscar}%"));
                }),
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
            'preciosReferencia' => Variante::preciosReferenciaPorId(),
            'metodosPago' => MetodoPago::cases(),
            'clientes' => Cliente::orderBy('nombre')->get(['id', 'nombre', 'cedula', 'saldo_favor']),
            'clientesEnMora' => Cliente::enMora()->pluck('id'),
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
                saldoFavorAplicado: $request->saldoFavorAplicadoParaServicio(),
                autorizarMora: $request->autorizarMora(),
            );
        } catch (StockInsuficienteException|SaldoFavorInsuficienteException|ClienteEnMoraException|PagoVentaInvalidoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Venta {$venta->numero} registrada.");
    }

    public function show(Venta $venta): View
    {
        $this->authorize('view', $venta);

        $venta->load([
            'lineas.variante.producto',
            'usuario',
            'anuladaPor',
            'cliente',
            'creditoAutorizadoPor',
            'abonos.usuario',
            'devoluciones.lineas',
        ]);

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

        try {
            DB::transaction(function () use ($venta) {
                $bloqueada = Venta::whereKey($venta->getKey())->lockForUpdate()->firstOrFail();

                if (! $bloqueada->puedeEntregarse()) {
                    throw VentaNoEntregableException::para($bloqueada);
                }

                $bloqueada->forceFill(['entregada_at' => now()])->save();
            });
        } catch (VentaNoEntregableException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Venta {$venta->numero} marcada como entregada.");
    }
}
