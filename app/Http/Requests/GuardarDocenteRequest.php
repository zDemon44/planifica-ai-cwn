<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-docencia') ?? false;
    }

    public function rules(): array
    {
        return ['nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'identificacion' => ['required', 'string', 'max:30', Rule::unique('docentes')->ignore($this->route('docente'))],
            'correo' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])]];
    }

    public function messages(): array
    {
        return ['required' => 'Completa el campo :attribute.', 'max' => 'El campo :attribute no debe superar :max caracteres.', 'unique' => 'Este valor ya está registrado.', 'email' => 'Ingresa un correo válido.', 'in' => 'Selecciona un estado válido.', 'exists' => 'Selecciona una opción válida.', 'integer' => 'Selecciona una opción válida.'];
    }
}
