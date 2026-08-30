<?php

namespace App\Http\Controllers;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Variante;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Panel principal (RF-020): consolida los indicadores del negocio y es la
 * puerta de entrada a los reportes.
 *
 * El Administrador ve ventas, ganancia, inventario y cartera. El Empleado no
 * accede a información financiera (criterio de aceptación §14): solo ve un
 * resumen de las ventas que registró hoy.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        if ($usuario->esEmpleado()) {
            return view('dashboard', ['empleado' => $this->resumenEmpleado($usuario)]);
        }

        return view('dashboard', ['admin' => $this->indicadoresAdmin()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resumenEmpleado(User $usuario): array
    {
        $hoy = Venta::confirmadas()
            ->where('usuario_id', $usuario->id)
            ->whereDate('fecha_venta', today())
            ->selectRaw('COUNT(*) as ventas, COALESCE(SUM(total), 0) as total')
            ->first();

        return [
            'ventas_hoy' => (int) $hoy->ventas,
            'total_hoy' => (string) $hoy->total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function indicadoresAdmin(): array
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        $hoy = Venta::confirmadas()
            ->whereDate('fecha_venta', today())
            ->selectRaw('COUNT(*) as ventas, COALESCE(SUM(total), 0) as total')
            ->first();

        $mes = Venta::confirmadas()
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->selectRaw('COUNT(*) as ventas, COALESCE(SUM(total), 0) as total')
            ->first();

        $gananciaMes = DB::table('venta_lineas as vl')
            ->join('ventas as v', 'v.id', '=', 'vl.venta_id')
            ->where('v.estado', EstadoVenta::Confirmada->value)
            ->whereBetween('v.fecha_venta', [$inicioMes, $finMes])
            ->selectRaw('COALESCE(SUM(vl.importe_linea - vl.costo_unitario_snapshot * vl.cantidad), 0) as ganancia')
            ->value('ganancia');

        return [
            'ventas_hoy' => (int) $hoy->ventas,
            'total_hoy' => (string) $hoy->total,
            'ventas_mes' => (int) $mes->ventas,
            'total_mes' => (string) $mes->total,
            'ganancia_mes' => (string) $gananciaMes,
            'variantes_stock_bajo' => Variante::stockBajo()->count(),
            'credito_por_cobrar' => (string) Venta::where('metodo_pago', MetodoPago::Credito)->sum('credito_saldo_pendiente'),
            'clientes_en_mora' => Cliente::enMora()->count(),
            'saldo_favor_clientes' => (string) Cliente::sum('saldo_favor'),
            'top_productos' => $this->topProductosDelMes($inicioMes, $finMes),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function topProductosDelMes(Carbon $desde, Carbon $hasta): Collection
    {
        return DB::table('venta_lineas as vl')
            ->join('ventas as v', 'v.id', '=', 'vl.venta_id')
            ->join('variantes as va', 'va.id', '=', 'vl.variante_id')
            ->join('productos as p', 'p.id', '=', 'va.producto_id')
            ->where('v.estado', EstadoVenta::Confirmada->value)
            ->whereBetween('v.fecha_venta', [$desde, $hasta])
            ->groupBy('p.id', 'p.nombre', 'p.codigo_interno')
            ->selectRaw('p.nombre, p.codigo_interno, SUM(vl.cantidad) as unidades, COALESCE(SUM(vl.importe_linea), 0) as ingreso')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();
    }
}
