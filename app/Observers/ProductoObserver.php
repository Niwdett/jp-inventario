<?php

namespace App\Observers;

use App\Models\Producto;
use App\Models\ProductoHistorial;

/**
 * Llena el historial de un producto (RF-016, bloque B3a) sin paquetes externos.
 *
 * - Al crear: una fila `campo = 'alta'`.
 * - Al editar: una fila por cada campo de {@see self::CAMPOS} que haya cambiado,
 *   con su valor anterior y su valor nuevo.
 * - Al desactivar / reactivar (soft-delete): una fila `campo = 'estado'`.
 *
 * `codigo_interno` y `categoria_id` no se auditan: son inmutables tras el alta
 * (el `UpdateProductoRequest` no los acepta). La foto tampoco: es un archivo,
 * no información de negocio, y su ruta interna no aporta a la auditoría.
 *
 * `usuario_id` sale de la sesión actual; es NULL cuando el cambio ocurre fuera
 * de una petición (seeders, consola, tests), igual que los ajustes de inventario.
 */
class ProductoObserver
{
    /**
     * Campos de `productos` cuyo cambio se registra en el historial.
     *
     * @var list<string>
     */
    private const CAMPOS = ['nombre', 'marca', 'precio_referencia', 'umbral_stock_bajo', 'proveedor'];

    public function created(Producto $producto): void
    {
        $this->registrar($producto, 'alta', null, null);
    }

    public function updated(Producto $producto): void
    {
        $cambios = $producto->getChanges();

        // El restore dispara `updated` al limpiar `deleted_at`; ese caso lo
        // registra `restored()`. El soft-delete no pasa por aquí (SoftDeletes
        // actualiza la fila sin `save()`).
        if (array_key_exists('deleted_at', $cambios)) {
            return;
        }

        foreach (array_keys($cambios) as $campo) {
            if (! in_array($campo, self::CAMPOS, true)) {
                continue;
            }

            $this->registrar(
                $producto,
                $campo,
                $this->normalizar($producto->getOriginal($campo)),
                $this->normalizar($producto->getAttribute($campo)),
            );
        }
    }

    public function deleted(Producto $producto): void
    {
        if ($producto->isForceDeleting()) {
            return;
        }

        $this->registrar($producto, 'estado', 'activo', 'inactivo');
    }

    public function restored(Producto $producto): void
    {
        $this->registrar($producto, 'estado', 'inactivo', 'activo');
    }

    private function registrar(Producto $producto, string $campo, ?string $anterior, ?string $nuevo): void
    {
        ProductoHistorial::create([
            'producto_id' => $producto->id,
            'usuario_id' => auth()->id(),
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_nuevo' => $nuevo,
        ]);
    }

    private function normalizar(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (string) $valor;
    }
}
