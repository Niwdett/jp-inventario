<?php

namespace App\Http\Controllers;

use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reporte de inventario disponible (RF-018). Solo lectura, solo Administrador.
 *
 * Es una foto del stock en el momento de la consulta (criterio de aceptación
 * §14): por cada variante activa, sus unidades, su costo promedio y el valor
 * del inventario (`stock × costo_promedio`), con totales por categoría y
 * generales. Por defecto oculta las variantes agotadas.
 */
class ReporteInventarioController extends Controller
{
    public function index(Request $request): View
    {
        $incluirAgotadas = $request->boolean('incluir_agotadas');

        $variantes = Variante::query()
            ->whereHas('producto')
            ->when(! $incluirAgotadas, fn ($query) => $query->where('stock', '>', 0))
            ->with(['producto:id,nombre,codigo_interno,categoria_id,umbral_stock_bajo', 'producto.categoria:id,nombre'])
            ->get()
            ->sortBy(fn (Variante $v) => mb_strtolower($v->producto->categoria->nombre.$v->producto->nombre.$v->talla.$v->color))
            ->values();

        $valorTotal = '0';
        $unidadesTotal = 0;

        foreach ($variantes as $variante) {
            $variante->valor_inventario = bcmul((string) $variante->stock, (string) $variante->costo_promedio, 2);
            $valorTotal = bcadd($valorTotal, $variante->valor_inventario, 2);
            $unidadesTotal += $variante->stock;
        }

        $porCategoria = $variantes
            ->groupBy(fn (Variante $v) => $v->producto->categoria->nombre)
            ->map(fn ($grupo) => [
                'unidades' => $grupo->sum('stock'),
                'valor' => $grupo->reduce(fn (string $acc, Variante $v) => bcadd($acc, $v->valor_inventario, 2), '0'),
            ])
            ->sortKeys();

        return view('admin.reportes.inventario', [
            'variantes' => $variantes,
            'porCategoria' => $porCategoria,
            'valorTotal' => $valorTotal,
            'unidadesTotal' => $unidadesTotal,
            'incluirAgotadas' => $incluirAgotadas,
        ]);
    }
}
