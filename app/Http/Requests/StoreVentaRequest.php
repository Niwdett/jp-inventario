<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
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

            'metodo_pago' => ['required', Rule::enum(MetodoPago::class)],
            'cliente_id' => [
                'nullable', 'integer',
                Rule::exists('clientes', 'id')->whereNull('deleted_at'),
            ],
            'saldo_favor_aplicado' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'autorizar_mora' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ids = collect($this->input('lineas', []))->pluck('variante_id')->filter();

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('lineas', 'Hay una variante repetida en dos líneas: únelas en una sola con la cantidad total.');
            }

            $aCredito = $this->input('metodo_pago') === MetodoPago::Credito->value;
            $saldo = $this->saldoFavorAplicadoParaServicio();
            $usaSaldo = bccomp($saldo, '0', 2) > 0;

            if (($aCredito || $usaSaldo) && ! $this->filled('cliente_id')) {
                $validator->errors()->add('cliente_id', 'Una venta a crédito o que aplica saldo a favor debe tener un cliente.');
            }

            if ($usaSaldo && bccomp($saldo, $this->totalCalculado(), 2) > 0) {
                $validator->errors()->add('saldo_favor_aplicado', 'El saldo a favor a aplicar no puede superar el total de la venta.');
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
     * Saldo a favor a aplicar, normalizado a string decimal ('0' si no se envía).
     */
    public function saldoFavorAplicadoParaServicio(): string
    {
        $valor = $this->input('saldo_favor_aplicado');

        return $valor === null || $valor === '' ? '0' : number_format((float) $valor, 2, '.', '');
    }

    /**
     * Autorización del Administrador para vender a crédito pese a la mora
     * (RN-09). Solo tiene efecto si quien registra es Administrador; el servicio
     * vuelve a comprobarlo.
     */
    public function autorizarMora(): bool
    {
        return $this->boolean('autorizar_mora');
    }

    /**
     * Total de la venta según las líneas enviadas, con la misma fórmula decimal
     * que el servicio (E2). Solo para validar el saldo a favor contra el total.
     */
    private function totalCalculado(): string
    {
        $total = '0';

        foreach ($this->input('lineas', []) as $linea) {
            $precio = is_numeric($linea['precio_unitario'] ?? null) ? (string) $linea['precio_unitario'] : '0';
            $cantidad = is_numeric($linea['cantidad'] ?? null) ? (string) (int) $linea['cantidad'] : '0';
            $descuento = is_numeric($linea['descuento_porcentaje'] ?? null) ? (string) $linea['descuento_porcentaje'] : '0';

            $bruto = bcmul($precio, $cantidad, 6);
            $factor = bcsub('1', bcdiv($descuento, '100', 6), 6);
            $total = bcadd($total, bcround(bcmul($bruto, $factor, 6), 2), 2);
        }

        return $total;
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
            'cliente_id' => 'cliente',
            'saldo_favor_aplicado' => 'saldo a favor',
        ];
    }
}
