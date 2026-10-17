<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-docencia') ?? false;
    }

    public function rules(): array
    {
        return ['nombre' => ['required', 'string', 'max:100', Rule::unique('materias')->ignore($this->route('materia'))],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])]];
    }

    public function messages(): array
    {
        return ['required' => 'Completa el campo :attribute.', 'max' => 'El campo :attribute no debe superar :max caracteres.', 'unique' => 'Este valor ya está registrado.', 'email' => 'Ingresa un correo válido.', 'in' => 'Selecciona un estado válido.', 'exists' => 'Selecciona una opción válida.', 'integer' => 'Selecciona una opción válida.'];
    }
}
