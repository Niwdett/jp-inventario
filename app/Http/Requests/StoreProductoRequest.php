<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de producto. Incluye la primera variante (talla/color): un producto
 * siempre nace con al menos una variante (A3).
 */
class StoreProductoRequest extends FormRequest
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
            'categoria_id' => [
                'required', 'integer',
                Rule::exists('categorias', 'id')->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'precio_referencia' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'umbral_stock_bajo' => ['required', 'integer', 'min:0', 'max:1000000'],
            'proveedor' => ['nullable', 'string', 'max:255'],

            'talla' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'categoria_id' => 'categoría',
            'precio_referencia' => 'precio de referencia',
            'umbral_stock_bajo' => 'umbral de stock bajo',
        ];
    }
}
