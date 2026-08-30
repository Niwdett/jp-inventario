<?php

namespace App\Http\Controllers;

use App\Enums\EstadoDevolucion;
use App\Enums\EstadoVenta;
use App\Http\Requests\ReportePeriodoRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reporte de ganancias (RF-019, RN-04, RN-05; flujo 4.6). Solo lectura, solo
 * Administrador.
 *
 * Ganancia de una línea = `importe_linea − costo_unitario_snapshot × cantidad`.
 * El snapshot es inmutable: cambios posteriores de costo o precio no alteran
 * ventas pasadas (RN-05). Todo el cálculo usa aritmética decimal.
 *
 * "Bruta" = ganancia de las ventas del periodo. "Neta" = bruta menos el efecto
 * de las devoluciones validadas en el periodo (por su fecha): se revierte lo
 * abonado como saldo a favor y, si la línea reintegró inventario, se recupera
 * su costo. Se ofrece la comparación con el periodo inmediato anterior.
 */
class ReporteGananciasController extends Controller
{
    public function index(ReportePeriodoRequest $request): View
    {
        $actual = $this->resumen($request->desde(), $request->hasta());

        $comparacion = null;
        if ($request->comparar()) {
            [$desdePrevio, $hastaPrevio] = $request->periodoAnterior();
            $comparacion = [
                'periodo' => ['desde' => $desdePrevio, 'hasta' => $hastaPrevio],
                'resumen' => $this->resumen($desdePrevio, $hastaPrevio),
            ];
        }

        return view('admin.reportes.ganancias', [
            'periodo' => $request->paraVista(),
            'resumen' => $actual,
            'porVenta' => $this->porVenta($request->desde(), $request->hasta()),
            'porProducto' => $this->porProducto($request->desde(), $request->hasta()),
            'comparacion' => $comparacion,
        ]);
    }

    /**
     * Totales de ganancia del periodo: bruta (ventas), reversión (devoluciones)
     * y neta.
     *
     * @return array<string, string|int>
     */
    private function resumen(Carbon $desde, Carbon $hasta): array
    {
        $ventas = DB::table('venta_lineas as vl')
            ->join('ventas as v', 'v.id', '=', 'vl.venta_id')
            ->where('v.estado', EstadoVenta::Confirmada->value)
            ->whereBetween('v.fecha_venta', [$desde, $hasta])
            ->selectRaw('COALESCE(SUM(vl.importe_linea), 0) as ingreso')
            ->selectRaw('COALESCE(SUM(vl.costo_unitario_snapshot * vl.cantidad), 0) as costo')
            ->first();

        $numeroVentas = DB::table('ventas')
            ->where('estado', EstadoVenta::Confirmada->value)
            ->whereBetween('fecha_venta', [$desde, $hasta])
            ->count();

        $devoluciones = $this->reversionDevoluciones($desde, $hasta);

        $ingreso = (string) $ventas->ingreso;
        $costo = (string) $ventas->costo;
        $gananciaBruta = bcsub($ingreso, $costo, 2);
        $gananciaNeta = bcsub($gananciaBruta, $devoluciones['ganancia_revertida'], 2);

        return [
            'ventas' => $numeroVentas,
            'ingreso' => $ingreso,
            'costo' => $costo,
            'ganancia_bruta' => $gananciaBruta,
            'ingreso_revertido' => $devoluciones['ingreso_revertido'],
            'costo_recuperado' => $devoluciones['costo_recuperado'],
            'ganancia_revertida' => $devoluciones['ganancia_revertida'],
            'ganancia_neta' => $gananciaNeta,
            'margen' => $this->margen($gananciaNeta, $ingreso),
        ];
    }

    /**
     * Efecto de las devoluciones validadas en el periodo (por `fecha`) sobre la
     * ganancia.
     *
     * @return array{ingreso_revertido: string, costo_recuperado: string, ganancia_revertida: string}
     */
    private function reversionDevoluciones(Carbon $desde, Carbon $hasta): array
    {
        $fila = DB::table('devolucion_lineas as dl')
            ->join('devoluciones as d', 'd.id', '=', 'dl.devolucion_id')
            ->join('venta_lineas as vl', 'vl.id', '=', 'dl.venta_linea_id')
            ->where('d.estado', EstadoDevolucion::Validada->value)
            ->whereBetween('d.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('COALESCE(SUM(dl.valor_unitario * dl.cantidad), 0) as ingreso_revertido')
            ->selectRaw('COALESCE(SUM(CASE WHEN dl.reintegra_inventario = 1 THEN vl.costo_unitario_snapshot * dl.cantidad ELSE 0 END), 0) as costo_recuperado')
            ->first();

        $ingresoRevertido = (string) $fila->ingreso_revertido;
        $costoRecuperado = (string) $fila->costo_recuperado;

        return [
            'ingreso_revertido' => bcadd($ingresoRevertido, '0', 2),
            'costo_recuperado' => bcadd($costoRecuperado, '0', 2),
            'ganancia_revertida' => bcsub($ingresoRevertido, $costoRecuperado, 2),
        ];
    }

    /**
     * Ganancia bruta de cada venta confirmada del periodo (RF-019 "por venta").
     *
     * @return Collection<int, object>
     */
    private function porVenta(Carbon $desde, Carbon $hasta): Collection
    {
        return DB::table('ventas as v')
            ->leftJoin('venta_lineas as vl', 'vl.venta_id', '=', 'v.id')
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->where('v.estado', EstadoVenta::Confirmada->value)
            ->whereBetween('v.fecha_venta', [$desde, $hasta])
            ->groupBy('v.id', 'v.numero', 'v.fecha_venta', 'c.nombre')
            ->selectRaw('v.id, v.numero, v.fecha_venta, c.nombre as cliente')
            ->selectRaw('COALESCE(SUM(vl.importe_linea), 0) as ingreso')
            ->selectRaw('COALESCE(SUM(vl.costo_unitario_snapshot * vl.cantidad), 0) as costo')
            ->orderBy('v.fecha_venta')
            ->orderBy('v.id')
            ->get()
            ->map(function (object $fila): object {
                $fila->ganancia = bcsub((string) $fila->ingreso, (string) $fila->costo, 2);

                return $fila;
            });
    }

    /**
     * Ganancia por producto en el periodo (RF-019 "por producto"), neta de las
     * devoluciones validadas del mismo periodo.
     *
     * @return Collection<int, object>
     */
    private function porProducto(Carbon $desde, Carbon $hasta): Collection
    {
        $ventas = DB::table('venta_lineas as vl')
            ->join('ventas as v', 'v.id', '=', 'vl.venta_id')
            ->join('variantes as va', 'va.id', '=', 'vl.variante_id')
            ->join('productos as p', 'p.id', '=', 'va.producto_id')
            ->where('v.estado', EstadoVenta::Confirmada->value)
            ->whereBetween('v.fecha_venta', [$desde, $hasta])
            ->groupBy('p.id', 'p.nombre', 'p.codigo_interno')
            ->selectRaw('p.id, p.nombre, p.codigo_interno')
            ->selectRaw('SUM(vl.cantidad) as unidades')
            ->selectRaw('COALESCE(SUM(vl.importe_linea), 0) as ingreso')
            ->selectRaw('COALESCE(SUM(vl.costo_unitario_snapshot * vl.cantidad), 0) as costo')
            ->get()
            ->keyBy('id');

        $devoluciones = DB::table('devolucion_lineas as dl')
            ->join('devoluciones as d', 'd.id', '=', 'dl.devolucion_id')
            ->join('venta_lineas as vl', 'vl.id', '=', 'dl.venta_linea_id')
            ->join('variantes as va', 'va.id', '=', 'vl.variante_id')
            ->join('productos as p', 'p.id', '=', 'va.producto_id')
            ->where('d.estado', EstadoDevolucion::Validada->value)
            ->whereBetween('d.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('p.id')
            ->selectRaw('p.id')
            ->selectRaw('SUM(dl.cantidad) as unidades_devueltas')
            ->selectRaw('COALESCE(SUM(dl.valor_unitario * dl.cantidad), 0) as ingreso_revertido')
            ->selectRaw('COALESCE(SUM(CASE WHEN dl.reintegra_inventario = 1 THEN vl.costo_unitario_snapshot * dl.cantidad ELSE 0 END), 0) as costo_recuperado')
            ->get()
            ->keyBy('id');

        return $ventas->map(function (object $fila) use ($devoluciones): object {
            $dev = $devoluciones->get($fila->id);

            $gananciaBruta = bcsub((string) $fila->ingreso, (string) $fila->costo, 2);
            $gananciaRevertida = $dev
                ? bcsub((string) $dev->ingreso_revertido, (string) $dev->costo_recuperado, 2)
                : '0.00';
            $gananciaNeta = bcsub($gananciaBruta, $gananciaRevertida, 2);

            return (object) [
                'nombre' => $fila->nombre,
                'codigo' => $fila->codigo_interno,
                'unidades' => (int) $fila->unidades,
                'ingreso' => bcadd((string) $fila->ingreso, '0', 2),
                'ganancia_bruta' => $gananciaBruta,
                'unidades_devueltas' => (int) ($dev->unidades_devueltas ?? 0),
                'ganancia_revertida' => $gananciaRevertida,
                'ganancia_neta' => $gananciaNeta,
                'margen' => $this->margen($gananciaNeta, (string) $fila->ingreso),
            ];
        })->sortByDesc(fn (object $fila) => (float) $fila->ganancia_neta)->values();
    }

    /**
     * Margen porcentual = ganancia / ingreso × 100, con un decimal. 0 si no hubo
     * ingreso.
     */
    private function margen(string $ganancia, string $ingreso): string
    {
        if (bccomp($ingreso, '0', 2) <= 0) {
            return '0.0';
        }

        return bcmul(bcdiv($ganancia, $ingreso, 6), '100', 1);
    }
}
