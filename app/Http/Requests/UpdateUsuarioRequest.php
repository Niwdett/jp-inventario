<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
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
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'rol' => ['required', Rule::enum(Rol::class)],
            // Opcional: solo si el Administrador quiere reestablecer la contraseña.
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * Invariante: el sistema nunca puede quedarse sin ningún Administrador activo.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var User $usuario */
                $usuario = $this->route('usuario');

                $degradaUltimoAdmin = $usuario->rol === Rol::Administrador
                    && $this->input('rol') !== Rol::Administrador->value
                    && User::where('rol', Rol::Administrador)->count() === 1;

                if ($degradaUltimoAdmin) {
                    $validator->errors()->add('rol', 'No puedes quitar el rol de Administrador al último administrador activo.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'rol' => 'rol',
            'password' => 'contraseña',
        ];
    }
}
