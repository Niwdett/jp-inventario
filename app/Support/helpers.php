<?php

declare(strict_types=1);

if (! function_exists('money')) {
    /**
     * Formatea un importe para mostrarlo en pantalla.
     *
     * Formato único de la aplicación: miles con coma y decimales con punto
     * (p. ej. 1,234.50). Solo presentación: para cálculos con dinero se usa
     * BCMath en los servicios, nunca este helper.
     *
     * @param  int|float|string|null  $value  importe (normalmente un decimal de la BD como string)
     */
    function money(int|float|string|null $value, int $decimals = 2, bool $symbol = false): string
    {
        $formatted = number_format((float) ($value ?? 0), $decimals, '.', ',');

        return $symbol ? '$ '.$formatted : $formatted;
    }
}
