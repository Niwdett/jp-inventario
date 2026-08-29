<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntradaInventarioRequest extends FormRequest
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
            'variante_id' => [
                'required', 'integer',
                Rule::exists('variantes', 'id')->whereNull('deleted_at'),
            ],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'costo_unitario' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'proveedor' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'variante_id' => 'variante',
            'costo_unitario' => 'costo unitario',
        ];
    }
}
