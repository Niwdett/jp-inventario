<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVarianteRequest extends FormRequest
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
        $productoId = $this->route('producto')->id;

        return [
            'talla' => ['required', 'string', 'max:50'],
            'color' => [
                'required', 'string', 'max:50',
                Rule::unique('variantes', 'color')->where(fn ($query) => $query
                    ->where('producto_id', $productoId)
                    ->where('talla', $this->input('talla'))
                    ->whereNull('deleted_at')),
            ],
            'codigo' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.unique' => 'Ya existe una variante con esa combinación de talla y color.',
        ];
    }
}
