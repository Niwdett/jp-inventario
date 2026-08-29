<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Observers\ProductoObserver;
use Illuminate\View\View;

/**
 * Historial de un producto (RF-016; bloque B3a) — solo lectura, solo
 * Administrador.
 *
 * Muestra el alta y cada modificación posterior de la información del producto.
 * Las entradas las genera {@see ProductoObserver}; aquí solo se
 * listan. Se acepta un producto eliminado (`withTrashed` en la ruta) para poder
 * consultar su historia después de desactivarlo.
 */
class ProductoHistorialController extends Controller
{
    public function index(Producto $producto): View
    {
        $entradas = $producto->historial()
            ->with('usuario:id,name')
            ->latest('id')
            ->paginate(30);

        return view('admin.productos.historial', compact('producto', 'entradas'));
    }
}
