<?php

namespace App\Http\Controllers;

use App\Exceptions\EntradaNoAnulableException;
use App\Exceptions\StockNegativoAlAnularEntradaException;
use App\Http\Requests\AnularEntradaRequest;
use App\Http\Requests\StoreEntradaInventarioRequest;
use App\Models\EntradaInventario;
use App\Models\Variante;
use App\Services\Inventario\AnularEntrada;
use App\Services\Inventario\RegistrarEntrada;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Entradas de mercancía (RF-005). Solo Administrador.
 *
 * El registro (transacción + bloqueo + recálculo de costo promedio) vive en
 * el servicio {@see RegistrarEntrada}; la anulación (decisión A4) en
 * {@see AnularEntrada}. El controlador solo valida y delega.
 */
class EntradaInventarioController extends Controller
{
    public function index(): View
    {
        $entradas = EntradaInventario::with(['variante.producto', 'usuario', 'anuladaPor'])
            ->latest('fecha')
            ->latest('id')
            ->paginate(20);

        return view('admin.inventario.entradas.index', compact('entradas'));
    }

    public function create(): View
    {
        return view('admin.inventario.entradas.create', [
            'variantes' => Variante::opcionesParaSelect(),
            'varianteSeleccionada' => request()->integer('variante_id') ?: null,
        ]);
    }

    public function store(StoreEntradaInventarioRequest $request, RegistrarEntrada $registrar): RedirectResponse
    {
        $variante = Variante::findOrFail($request->validated('variante_id'));

        $registrar->ejecutar(
            variante: $variante,
            cantidad: (int) $request->validated('cantidad'),
            costoUnitario: (string) $request->validated('costo_unitario'),
            fecha: (string) $request->validated('fecha'),
            proveedor: $request->validated('proveedor'),
            usuario: $request->user(),
        );

        return redirect()
            ->route('admin.inventario.entradas.index')
            ->with('status', 'Entrada registrada y costo promedio actualizado.');
    }

    public function anular(AnularEntradaRequest $request, EntradaInventario $entrada, AnularEntrada $anular): RedirectResponse
    {
        try {
            $resultado = $anular->ejecutar($entrada, $request->validated('motivo'), $request->user());
        } catch (EntradaNoAnulableException|StockNegativoAlAnularEntradaException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.inventario.entradas.index')
            ->with('status', $resultado->mensaje());
    }
}
