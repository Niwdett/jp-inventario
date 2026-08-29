<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Variante;
use Illuminate\View\View;

/**
 * Libro de movimientos de inventario (bloque B3b) — solo lectura. Solo Administrador.
 */
class MovimientoInventarioController extends Controller
{
    public function index(): View
    {
        $movimientos = MovimientoInventario::with(['variante.producto', 'usuario'])
            ->when(request()->integer('variante_id'), fn ($q, $id) => $q->where('variante_id', $id))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventario.movimientos.index', [
            'movimientos' => $movimientos,
            'variantes' => Variante::opcionesParaSelect(),
            'varianteSeleccionada' => request()->integer('variante_id') ?: null,
        ]);
    }
}
