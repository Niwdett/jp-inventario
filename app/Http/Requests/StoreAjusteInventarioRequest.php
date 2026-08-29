<?php

namespace App\Http\Requests;

use App\Models\Variante;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAjusteInventarioRequest extends FormRequest
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
            'cantidad_nueva' => [
                'required', 'integer', 'min:0', 'max:1000000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $variante = Variante::find($this->input('variante_id'));

                    if ($variante && (int) $value === $variante->stock) {
                        $fail('La cantidad contada es igual al stock actual: no hay nada que ajustar.');
                    }
                },
            ],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'variante_id' => 'variante',
            'cantidad_nueva' => 'cantidad contada',
        ];
    }
}
