<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAjusteInventarioRequest;
use App\Models\AjusteInventario;
use App\Models\Variante;
use App\Services\Inventario\AjustarInventario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ajustes manuales de inventario (RF-006). Solo Administrador.
 *
 * El ajuste (transacción + bloqueo + movimiento sin usuario, RN-15) vive en el
 * servicio {@see AjustarInventario}.
 */
class AjusteInventarioController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);
        $buscar = trim((string) $request->query('buscar'));

        $ajustes = AjusteInventario::with('variante.producto')
            ->when($buscar !== '', fn (Builder $query) => $query->whereHas(
                'variante.producto',
                fn (Builder $producto) => $producto
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo_interno', 'like', "%{$buscar}%"),
            ))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventario.ajustes.index', compact('ajustes', 'buscar'));
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
