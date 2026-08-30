<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Resuelve el periodo de un reporte (RF-017, RF-019) a partir de la query
 * string. Comparten esta clase el reporte de ventas y el de ganancias.
 *
 * - `preset`: `hoy` | `semana` | `mes` (por defecto) | `personalizado`.
 * - `desde` / `hasta`: obligatorias solo si `preset = personalizado`.
 * - `comparar`: si está presente, el de ganancias añade el periodo inmediato
 *   anterior de la misma duración.
 *
 * Los límites se devuelven como inicio y fin de día para incluir todas las
 * ventas de las fechas elegidas.
 */
class ReportePeriodoRequest extends FormRequest
{
    private const PRESETS = ['hoy', 'semana', 'mes', 'personalizado'];

    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preset' => ['nullable', 'string', 'in:'.implode(',', self::PRESETS)],
            'desde' => ['nullable', 'date', 'required_if:preset,personalizado'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde', 'required_if:preset,personalizado'],
            'comparar' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'desde' => 'fecha desde',
            'hasta' => 'fecha hasta',
        ];
    }

    public function preset(): string
    {
        $preset = $this->validated('preset');

        if ($preset === 'personalizado' || ($this->filled('desde') && $this->filled('hasta') && $preset === null)) {
            return 'personalizado';
        }

        return in_array($preset, self::PRESETS, true) ? $preset : 'mes';
    }

    public function desde(): Carbon
    {
        return match ($this->preset()) {
            'hoy' => Carbon::today(),
            'semana' => Carbon::now()->startOfWeek(),
            'personalizado' => Carbon::parse($this->validated('desde'))->startOfDay(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    public function hasta(): Carbon
    {
        return match ($this->preset()) {
            'hoy' => Carbon::today()->endOfDay(),
            'semana' => Carbon::now()->endOfWeek(),
            'personalizado' => Carbon::parse($this->validated('hasta'))->endOfDay(),
            default => Carbon::now()->endOfMonth(),
        };
    }

    /**
     * Periodo inmediato anterior, de la misma cantidad de días, para la
     * comparación del reporte de ganancias (RF-019).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodoAnterior(): array
    {
        $desdeActual = $this->desde();
        $dias = (int) $desdeActual->copy()->startOfDay()->diffInDays($this->hasta()->copy()->startOfDay()) + 1;

        $hasta = $desdeActual->copy()->subDay()->endOfDay();
        $desde = $desdeActual->copy()->subDays($dias)->startOfDay();

        return [$desde, $hasta];
    }

    public function comparar(): bool
    {
        return $this->boolean('comparar');
    }

    /**
     * Datos del periodo para la vista y el formulario de filtro.
     *
     * @return array{preset: string, etiqueta: string, desde: Carbon, hasta: Carbon, comparar: bool}
     */
    public function paraVista(): array
    {
        return [
            'preset' => $this->preset(),
            'etiqueta' => $this->etiqueta(),
            'desde' => $this->desde(),
            'hasta' => $this->hasta(),
            'comparar' => $this->comparar(),
        ];
    }

    public function etiqueta(): string
    {
        return match ($this->preset()) {
            'hoy' => 'Hoy',
            'semana' => 'Esta semana',
            'personalizado' => $this->desde()->format('Y-m-d').' — '.$this->hasta()->format('Y-m-d'),
            default => 'Este mes',
        };
    }
}
