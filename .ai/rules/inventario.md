---
paths:
  - 'app/Services/Inventario/**'
---

# Inventario

## Anulación de entradas (A4): reconstruir, no invertir
Corregir una entrada de mercancía = anularla (`AnularEntrada`), nunca editarla ni borrarla. `AnularEntrada` abre txn + `lockForUpdate` sobre entrada y variante, marca `anulada_at/anulada_por/motivo_anulacion`, y recalcula `variantes.stock`/`costo_promedio` con `ReconstruirCostoVariante::calcular()` — que reproduce TODO el ledger de la variante por `id` (ignora las entradas anuladas y sus movimientos `anulacion_entrada`). Nunca se deshace el promedio con aritmética inversa. Guardas: doble anulación → `EntradaNoAnulableException`; si la reproducción deja stock negativo en algún punto (`faltante > 0`) → `StockNegativoAlAnularEntradaException` (revierte todo; hay que hacer un ajuste físico antes). `venta_lineas.costo_unitario_snapshot` NUNCA se toca (RN-05). El movimiento `anulacion_entrada` SÍ registra `usuario_id` (a diferencia del `ajuste`, RN-15). `RegistrarEntrada`/`AjustarInventario` no se tocaron. La fórmula del promedio se duplica en `ReconstruirCostoVariante` a propósito.
