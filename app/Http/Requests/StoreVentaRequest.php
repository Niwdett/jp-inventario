<?php

namespace App\Http\Requests;

use App\Models\Venta;
use App\Services\Ventas\RegistrarVenta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Venta::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.variante_id' => [
                'required', 'integer',
                Rule::exists('variantes', 'id')->whereNull('deleted_at'),
            ],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'lineas.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Sprint 3: solo venta de contado. El crédito llega en el Sprint 4.
            'metodo_pago' => ['required', Rule::in(['efectivo', 'transferencia'])],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ids = collect($this->input('lineas', []))->pluck('variante_id')->filter();

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('lineas', 'Hay una variante repetida en dos líneas: únelas en una sola con la cantidad total.');
            }
        });
    }

    /**
     * Líneas ya normalizadas para {@see RegistrarVenta}:
     * los importes como string para la aritmética decimal.
     *
     * @return list<array{variante_id: int, cantidad: int, precio_unitario: string, descuento_porcentaje: string|null}>
     */
    public function lineasParaServicio(): array
    {
        return collect($this->validated('lineas'))
            ->map(fn (array $linea): array => [
                'variante_id' => (int) $linea['variante_id'],
                'cantidad' => (int) $linea['cantidad'],
                'precio_unitario' => (string) $linea['precio_unitario'],
                'descuento_porcentaje' => isset($linea['descuento_porcentaje']) && $linea['descuento_porcentaje'] !== null
                    ? (string) $linea['descuento_porcentaje']
                    : null,
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lineas.*.variante_id' => 'variante',
            'lineas.*.cantidad' => 'cantidad',
            'lineas.*.precio_unitario' => 'precio',
            'lineas.*.descuento_porcentaje' => 'descuento',
            'metodo_pago' => 'método de pago',
        ];
    }
}
