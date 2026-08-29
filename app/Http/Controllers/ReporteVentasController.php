<?php

namespace App\Http\Controllers;

use App\Enums\EstadoDevolucion;
use App\Http\Requests\ReportePeriodoRequest;
use App\Models\Devolucion;
use App\Models\Venta;
use Illuminate\View\View;

/**
 * Reporte de ventas por periodo (RF-017; flujo 4.6). Solo lectura, solo
 * Administrador.
 *
 * Reemplaza la suma manual del cuaderno / Excel: totales del periodo, desglose
 * por método de pago y por día. Solo cuenta ventas confirmadas; las anuladas no
 * existieron para el negocio. Las devoluciones del periodo se muestran aparte
 * como dato informativo (su efecto en la ganancia se ve en RF-019).
 */
class ReporteVentasController extends Controller
{
    public function index(ReportePeriodoRequest $request): View
    {
        $desde = $request->desde();
        $hasta = $request->hasta();

        $enPeriodo = fn () => Venta::confirmadas()->whereBetween('fecha_venta', [$desde, $hasta]);

        $resumen = $enPeriodo()
            ->selectRaw('COUNT(*) as ventas')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(descuento_total), 0) as descuento')
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->selectRaw('COALESCE(SUM(saldo_favor_aplicado), 0) as saldo_favor')
            ->first();

        $porMetodo = $enPeriodo()
            ->selectRaw('metodo_pago, COUNT(*) as ventas, COALESCE(SUM(total), 0) as total')
            ->groupBy('metodo_pago')
            ->get();

        $porDia = $enPeriodo()
            ->selectRaw('DATE(fecha_venta) as dia, COUNT(*) as ventas, COALESCE(SUM(total), 0) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $devoluciones = Devolucion::query()
            ->where('estado', EstadoDevolucion::Validada)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(saldo_generado), 0) as saldo_generado')
            ->first();

        return view('admin.reportes.ventas', [
            'periodo' => $request->paraVista(),
            'resumen' => $resumen,
            'porMetodo' => $porMetodo,
            'porDia' => $porDia,
            'devoluciones' => $devoluciones,
        ]);
    }
}
