<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerarPeriodoIaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esDocente() ?? false;
    }

    public function rules(): array
    {
        return [
            'asignacion_id' => ['required', 'integer', 'exists:asignaciones,id'],
            'destreza_curricular_id' => ['required', 'integer', 'exists:destrezas_curriculares,id'],
            'indicador_evaluacion_id' => ['required', 'integer', 'exists:indicadores_evaluacion,id'],
            'duracion_minutos' => ['required', 'integer', 'between:45,360', 'multiple_of:45'],
            'titulo_unidad' => ['nullable', 'string', 'max:255'],
            'tema' => ['nullable', 'string', 'max:500'],
            'indicaciones' => ['nullable', 'string', 'max:600'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Selecciona la destreza y su indicador antes de generar el período.',
            'exists' => 'La selección curricular no es válida.',
            'duracion_minutos.multiple_of' => 'La duración debe corresponder a períodos de 45 minutos.',
        ];
    }
}
