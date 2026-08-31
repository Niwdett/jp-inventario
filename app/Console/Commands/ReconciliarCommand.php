<?php

namespace App\Console\Commands;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Models\Cliente;
use App\Models\Variante;
use App\Models\Venta;
use App\Services\Inventario\ReconstruirCostoVariante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconciliación de los valores cacheados contra sus libros (pendiente 🟠 de la
 * auditoría de cierre del MVP).
 *
 * Comprueba tres invariantes del diseño:
 *
 *  1. `variantes.stock` / `costo_promedio` = reproducción del ledger
 *     `movimientos_inventario` ({@see ReconstruirCostoVariante}).
 *  2. `clientes.saldo_favor` = Σ `saldo_favor_movimientos.monto` del cliente.
 *  3. `ventas.credito_saldo_pendiente` = `credito_monto` − Σ abonos
 *     (y = 0 en las ventas anuladas).
 *
 * Por defecto es de solo lectura y termina con código de salida 1 si encuentra
 * alguna discrepancia (útil para un cron de monitoreo). Con `--fix` corrige cada
 * valor cacheado a partir de su libro, dentro de una transacción y con la fila
 * bloqueada.
 *
 *   php artisan jp:reconciliar
 *   php artisan jp:reconciliar --fix
 */
class ReconciliarCommand extends Command
{
    protected $signature = 'jp:reconciliar {--fix : Corrige cada valor cacheado a partir de su libro}';

    protected $description = 'Verifica (y opcionalmente corrige) stock, saldo a favor y crédito pendiente contra sus libros';

    public function handle(ReconstruirCostoVariante $reconstruir): int
    {
        $fix = (bool) $this->option('fix');

        $inventario = $this->revisarInventario($reconstruir, $fix);
        $saldoFavor = $this->revisarSaldoFavor($fix);
        $credito = $this->revisarCredito($fix);

        $total = $inventario + $saldoFavor + $credito;

        $this->newLine();

        if ($total === 0) {
            $this->info('Todo cuadra: no se encontraron discrepancias.');

            return self::SUCCESS;
        }

        if ($fix) {
            $this->info("Se corrigieron {$total} discrepancia(s).");

            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$total} discrepancia(s). Ejecuta 'php artisan jp:reconciliar --fix' para corregirlas.");

        return self::FAILURE;
    }

    /**
     * Invariante 1: stock y costo promedio de cada variante contra el ledger.
     */
    private function revisarInventario(ReconstruirCostoVariante $reconstruir, bool $fix): int
    {
        $filas = [];

        Variante::withTrashed()->with('producto:id,nombre,codigo_interno')->chunkById(200, function ($variantes) use ($reconstruir, $fix, &$filas) {
            foreach ($variantes as $variante) {
                $esperado = $reconstruir->calcular($variante);

                if ($esperado['faltante'] > 0) {
                    $filas[] = [$this->etiquetaVariante($variante), 'ledger inválido', "stock negativo en el histórico (déficit {$esperado['faltante']})", '— requiere ajuste físico —'];

                    continue;
                }

                $stockOk = $variante->stock === $esperado['stock'];
                $costoOk = bccomp((string) $variante->costo_promedio, $esperado['costo_promedio'], 4) === 0;

                if ($stockOk && $costoOk) {
                    continue;
                }

                $filas[] = [
                    $this->etiquetaVariante($variante),
                    'stock '.$variante->stock.' / costo '.$variante->costo_promedio,
                    'stock '.$esperado['stock'].' / costo '.$esperado['costo_promedio'],
                    $fix ? 'corregido' : 'pendiente',
                ];

                if ($fix) {
                    DB::transaction(function () use ($variante, $esperado) {
                        $bloqueada = Variante::whereKey($variante->id)->lockForUpdate()->firstOrFail();
                        $bloqueada->stock = $esperado['stock'];
                        $bloqueada->costo_promedio = $esperado['costo_promedio'];
                        $bloqueada->save();
                    });
                }
            }
        });

        $this->reportar('Inventario (stock ↔ ledger)', ['Variante', 'Valor actual', 'Valor esperado', 'Estado'], $filas);

        return count($filas);
    }

    /**
     * Invariante 2: `clientes.saldo_favor` contra su libro.
     */
    private function revisarSaldoFavor(bool $fix): int
    {
        $filas = [];

        Cliente::withTrashed()->withSum('saldoFavorMovimientos as saldo_libro', 'monto')->chunkById(200, function ($clientes) use ($fix, &$filas) {
            foreach ($clientes as $cliente) {
                $esperado = number_format((float) ($cliente->saldo_libro ?? 0), 2, '.', '');

                if (bccomp((string) $cliente->saldo_favor, $esperado, 2) === 0) {
                    continue;
                }

                $filas[] = [
                    "#{$cliente->id} {$cliente->nombre}",
                    (string) $cliente->saldo_favor,
                    $esperado,
                    $fix ? 'corregido' : 'pendiente',
                ];

                if ($fix) {
                    DB::transaction(function () use ($cliente, $esperado) {
                        $bloqueado = Cliente::withTrashed()->whereKey($cliente->id)->lockForUpdate()->firstOrFail();
                        $bloqueado->saldo_favor = $esperado;
                        $bloqueado->save();
                    });
                }
            }
        });

        $this->reportar('Saldo a favor (clientes.saldo_favor ↔ libro)', ['Cliente', 'Cacheado', 'Libro', 'Estado'], $filas);

        return count($filas);
    }

    /**
     * Invariante 3: `ventas.credito_saldo_pendiente` contra `credito_monto` − Σ abonos.
     */
    private function revisarCredito(bool $fix): int
    {
        $filas = [];

        Venta::query()
            ->where('metodo_pago', MetodoPago::Credito)
            ->whereNotNull('credito_monto')
            ->withSum('abonos as total_abonado', 'monto')
            ->chunkById(200, function ($ventas) use ($fix, &$filas) {
                foreach ($ventas as $venta) {
                    $esperado = $venta->estado === EstadoVenta::Anulada
                        ? '0.00'
                        : bcsub((string) $venta->credito_monto, number_format((float) ($venta->total_abonado ?? 0), 2, '.', ''), 2);

                    if (bccomp((string) $venta->credito_saldo_pendiente, $esperado, 2) === 0) {
                        continue;
                    }

                    $filas[] = [
                        $venta->numero,
                        (string) $venta->credito_saldo_pendiente,
                        $esperado,
                        $fix ? 'corregido' : 'pendiente',
                    ];

                    if ($fix) {
                        DB::transaction(function () use ($venta, $esperado) {
                            $bloqueada = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();
                            $bloqueada->credito_saldo_pendiente = $esperado;
                            $bloqueada->save();
                        });
                    }
                }
            });

        $this->reportar('Crédito pendiente (ventas.credito_saldo_pendiente ↔ abonos)', ['Venta', 'Cacheado', 'Esperado', 'Estado'], $filas);

        return count($filas);
    }

    private function etiquetaVariante(Variante $variante): string
    {
        return $variante->producto
            ? "{$variante->producto->codigo_interno} {$variante->etiqueta()}"
            : "variante #{$variante->id}";
    }

    /**
     * @param  list<string>  $cabeceras
     * @param  list<array<int, string>>  $filas
     */
    private function reportar(string $titulo, array $cabeceras, array $filas): void
    {
        $this->newLine();

        if ($filas === []) {
            $this->line("<info>✓</info> {$titulo}");

            return;
        }

        $this->line("<comment>✗ {$titulo}</comment>");
        $this->table($cabeceras, $filas);
    }
}
