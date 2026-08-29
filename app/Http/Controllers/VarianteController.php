<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVarianteRequest;
use App\Http\Requests\UpdateVarianteRequest;
use App\Models\Producto;
use App\Models\Variante;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Variantes de un producto (RF-004). Solo Administrador.
 *
 * No se puede eliminar la última variante activa de un producto (A3: un
 * producto siempre tiene al menos una variante). `stock` y `costo_promedio` no
 * se tocan aquí: cambian por entradas, ventas y ajustes de inventario.
 */
class VarianteController extends Controller
{
    public function store(StoreVarianteRequest $request, Producto $producto): RedirectResponse
    {
        $producto->variantes()->create([
            'talla' => trim((string) $request->validated('talla')),
            'color' => trim((string) $request->validated('color')),
            'codigo' => $request->validated('codigo'),
        ]);

        return redirect()
            ->route('admin.productos.show', $producto)
            ->with('status', 'Variante agregada.');
    }

    public function edit(Producto $producto, Variante $variante): View
    {
        abort_unless($variante->producto_id === $producto->id, 404);

        return view('admin.variantes.edit', compact('producto', 'variante'));
    }

    public function update(UpdateVarianteRequest $request, Producto $producto, Variante $variante): RedirectResponse
    {
        abort_unless($variante->producto_id === $producto->id, 404);

        $variante->update([
            'talla' => trim((string) $request->validated('talla')),
            'color' => trim((string) $request->validated('color')),
            'codigo' => $request->validated('codigo'),
        ]);

        return redirect()
            ->route('admin.productos.show', $producto)
            ->with('status', 'Variante actualizada.');
    }

    public function destroy(Producto $producto, Variante $variante): RedirectResponse
    {
        abort_unless($variante->producto_id === $producto->id, 404);

        if ($producto->variantes()->count() <= 1) {
            return back()->with('error', 'Un producto debe conservar al menos una variante.');
        }

        $variante->delete();

        return redirect()
            ->route('admin.productos.show', $producto)
            ->with('status', 'Variante eliminada.');
    }
}
