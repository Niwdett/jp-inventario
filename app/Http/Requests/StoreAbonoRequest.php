<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbonoRequest extends FormRequest
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
            'monto' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function montoParaServicio(): string
    {
        return number_format((float) $this->validated('monto'), 2, '.', '');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'monto' => 'monto del abono',
            'fecha' => 'fecha del abono',
        ];
    }
}
