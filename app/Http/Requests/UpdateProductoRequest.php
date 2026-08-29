<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de producto. No se editan aquí:
 * - `codigo_interno` (se fijó al crear),
 * - `categoria_id` (el código quedaría inconsistente con el prefijo),
 * - las variantes (tienen su propia pantalla).
 */
class UpdateProductoRequest extends FormRequest
{
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
            'nombre' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'precio_referencia' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'umbral_stock_bajo' => ['required', 'integer', 'min:0', 'max:1000000'],
            'proveedor' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'precio_referencia' => 'precio de referencia',
            'umbral_stock_bajo' => 'umbral de stock bajo',
        ];
    }
}
