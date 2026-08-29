<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAjusteInventarioRequest;
use App\Models\AjusteInventario;
use App\Models\Variante;
use App\Services\Inventario\AjustarInventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Ajustes manuales de inventario (RF-006). Solo Administrador.
 *
 * El ajuste (transacción + bloqueo + movimiento sin usuario, RN-15) vive en el
 * servicio {@see AjustarInventario}.
 */
class AjusteInventarioController extends Controller
{
    public function index(): View
    {
        $ajustes = AjusteInventario::with('variante.producto')
            ->latest('id')
            ->paginate(20);

        return view('admin.inventario.ajustes.index', compact('ajustes'));
    }

    public function create(): View
    {
        return view('admin.inventario.ajustes.create', [
            'variantes' => Variante::opcionesParaSelect(),
            'varianteSeleccionada' => request()->integer('variante_id') ?: null,
        ]);
    }

    public function store(StoreAjusteInventarioRequest $request, AjustarInventario $ajustar): RedirectResponse
    {
        $variante = Variante::findOrFail($request->validated('variante_id'));

        $ajustar->ejecutar(
            variante: $variante,
            cantidadNueva: (int) $request->validated('cantidad_nueva'),
            motivo: $request->validated('motivo'),
        );

        return redirect()
            ->route('admin.inventario.ajustes.index')
            ->with('status', 'Inventario ajustado al conteo físico.');
    }
}
