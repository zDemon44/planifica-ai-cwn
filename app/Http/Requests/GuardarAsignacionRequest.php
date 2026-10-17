<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-docencia') ?? false;
    }

    public function rules(): array
    {
        return ['docente_id' => ['required', 'integer', 'exists:docentes,id'],
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'seccion_id' => ['required', 'integer', 'exists:secciones,id']];
    }

    public function messages(): array
    {
        return ['required' => 'Completa el campo :attribute.', 'max' => 'El campo :attribute no debe superar :max caracteres.', 'unique' => 'Este valor ya está registrado.', 'email' => 'Ingresa un correo válido.', 'in' => 'Selecciona un estado válido.', 'exists' => 'Selecciona una opción válida.', 'integer' => 'Selecciona una opción válida.'];
    }
}
