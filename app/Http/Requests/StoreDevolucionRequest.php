<?php

namespace App\Http\Requests;

use App\Enums\EstadoDevolucion;
use App\Services\Devoluciones\RegistrarDevolucion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDevolucionRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'min:3', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoDevolucion::class)],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'lineas' => ['required', 'array'],
            'lineas.*.incluir' => ['nullable', 'boolean'],
            'lineas.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'lineas.*.reintegra_inventario' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->lineasParaServicio() === []) {
                $validator->errors()->add('lineas', 'Marca al menos una línea con su cantidad a devolver.');
            }

            $idsValidos = $this->route('venta')->lineas()->pluck('id')->all();

            foreach ($this->lineasParaServicio() as $linea) {
                if (! in_array($linea['venta_linea_id'], $idsValidos, true)) {
                    $validator->errors()->add('lineas', 'Una de las líneas seleccionadas no pertenece a esta venta.');
                }
            }
        });
    }

    /**
     * Solo las líneas marcadas y con cantidad válida, normalizadas para el
     * servicio {@see RegistrarDevolucion}.
     *
     * @return list<array{venta_linea_id: int, cantidad: int, reintegra_inventario: bool}>
     */
    public function lineasParaServicio(): array
    {
        $resultado = [];

        foreach ((array) $this->input('lineas', []) as $ventaLineaId => $datos) {
            $incluir = filter_var($datos['incluir'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $cantidad = (int) ($datos['cantidad'] ?? 0);

            if (! $incluir || $cantidad < 1) {
                continue;
            }

            $resultado[] = [
                'venta_linea_id' => (int) $ventaLineaId,
                'cantidad' => $cantidad,
                'reintegra_inventario' => filter_var($datos['reintegra_inventario'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $resultado;
    }

    public function estadoParaServicio(): EstadoDevolucion
    {
        return EstadoDevolucion::from($this->validated('estado'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motivo' => 'motivo',
            'estado' => 'resultado de la devolución',
            'fecha' => 'fecha',
        ];
    }
}
