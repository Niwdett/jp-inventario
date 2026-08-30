<?php

namespace App\Services\Inventario;

use App\Models\EntradaInventario;

/**
 * Efecto concreto de anular una entrada (decisión A4.4): sirve para el mensaje
 * *"Costo promedio de <variante>: 45,0000 → 4,5000. Stock: 31 → 11."*
 */
readonly class ResultadoAnulacionEntrada
{
    public function __construct(
        public EntradaInventario $entrada,
        public string $etiquetaVariante,
        public string $costoAnterior,
        public string $costoNuevo,
        public int $stockAnterior,
        public int $stockNuevo,
    ) {}

    /**
     * Frase lista para mostrar al Administrador.
     */
    public function mensaje(): string
    {
        return sprintf(
            'Entrada anulada. Costo promedio de %s: %s → %s. Stock: %d → %d.',
            $this->etiquetaVariante,
            number_format((float) $this->costoAnterior, 4),
            number_format((float) $this->costoNuevo, 4),
            $this->stockAnterior,
            $this->stockNuevo,
        );
    }
}
