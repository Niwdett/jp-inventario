<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends FormRequest
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
        $categoriaId = $this->route('categoria')->id;

        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('categorias', 'nombre')->whereNull('deleted_at')->ignore($categoriaId),
            ],
            'prefijo_codigo' => [
                'required', 'string', 'alpha', 'min:2', 'max:10',
                Rule::unique('categorias', 'prefijo_codigo')->whereNull('deleted_at')->ignore($categoriaId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('prefijo_codigo')) {
            $this->merge([
                'prefijo_codigo' => mb_strtoupper(trim((string) $this->input('prefijo_codigo'))),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'prefijo_codigo' => 'prefijo de código',
        ];
    }
}
