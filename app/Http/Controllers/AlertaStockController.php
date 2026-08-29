<?php

namespace App\Http\Controllers;

use App\Models\Variante;
use Illuminate\View\View;

/**
 * Alertas de stock bajo (RF-007, RN-14). Solo lectura, solo Administrador.
 *
 * Lista las variantes activas cuyo stock está en o por debajo del umbral
 * configurado en su producto.
 */
class AlertaStockController extends Controller
{
    public function index(): View
    {
        $variantes = Variante::stockBajo()
            ->with('producto:id,nombre,codigo_interno,umbral_stock_bajo')
            ->orderBy('variantes.stock')
            ->get();

        return view('admin.inventario.alertas.index', compact('variantes'));
    }
}
