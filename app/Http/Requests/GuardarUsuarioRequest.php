<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-usuarios') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->route('usuario'))],
            'password' => [$this->route('usuario') ? 'nullable' : 'required', 'string', 'min:8', 'max:72', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'activo' => ['required', 'boolean'],
            'docente_id' => ['nullable', 'required_if:role,DOCENTE', 'prohibited_unless:role,DOCENTE', 'integer', 'exists:docentes,id', Rule::unique('users')->ignore($this->route('usuario'))],
        ];
    }

    public function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function messages(): array
    {
        return ['required' => 'Completa el campo :attribute.', 'required_if' => 'Selecciona la ficha del docente.', 'unique' => 'Este correo o docente ya tiene una cuenta.', 'exists' => 'Selecciona un docente activo.', 'prohibited_unless' => 'Solo las cuentas Docente se vinculan con una ficha docente.', 'email' => 'Ingresa un correo válido.', 'confirmed' => 'Las contraseñas no coinciden.', 'min' => 'La contraseña debe tener al menos :min caracteres.', 'max' => 'El campo :attribute supera el máximo de :max caracteres.', 'in' => 'Selecciona un rol válido.', 'boolean' => 'Selecciona un estado válido.'];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'role' => 'rol', 'activo' => 'estado'];
    }
}
