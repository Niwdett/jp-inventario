---
paths:
  - 'app/Http/Controllers/Reporte*.php'
---

# Controllers

## Reportes (RF-017/018/019): agregados de dinero y periodo
Los 3 reportes (ReporteVentasController, ReporteInventarioController, ReporteGananciasController) son solo lectura, solo Admin, y NO persisten nada.
- Periodo: `App\Http\Requests\ReportePeriodoRequest` (presets hoy/semana/mes + personalizado, y `periodoAnterior()` para la comparación). Compartido por ventas y ganancias.
- Ganancia: al vuelo = `importe_linea − costo_unitario_snapshot × cantidad` (RN-04/RN-05, snapshot inmutable). NO añadir columna `ganancia`.
- Sumas de dinero: SQL `SUM()` sobre columnas DECIMAL (se mantiene DECIMAL en MySQL) o BCMath en PHP. Nunca float.
- "Neta" descuenta devoluciones VALIDADAS por `devoluciones.fecha` en el periodo: revierte `valor_unitario×cant` y, si `reintegra_inventario`, recupera `costo_snapshot×cant`.
- Solo ventas `estado = confirmada`.
