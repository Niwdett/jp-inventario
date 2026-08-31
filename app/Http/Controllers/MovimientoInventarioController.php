<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Libro de movimientos de inventario (bloque B3b) — solo lectura. Solo Administrador.
 */
class MovimientoInventarioController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);
        $buscar = trim((string) $request->query('buscar'));

        $movimientos = MovimientoInventario::with(['variante.producto', 'usuario'])
            ->when($buscar !== '', fn (Builder $query) => $query->whereHas(
                'variante.producto',
                fn (Builder $producto) => $producto
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo_interno', 'like', "%{$buscar}%"),
            ))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventario.movimientos.index', compact('movimientos', 'buscar'));
    }
}
