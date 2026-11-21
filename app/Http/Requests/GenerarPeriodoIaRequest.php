<?php

namespace App\Http\Requests;

use App\Services\GeneradorPeriodoPlanificacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'enfoque' => ['nullable', 'string', Rule::in(GeneradorPeriodoPlanificacion::CAMPOS_ENFOCABLES)],
            'contenido_actual' => ['nullable', 'array:'.implode(',', GeneradorPeriodoPlanificacion::CAMPOS)],
            'contenido_actual.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Selecciona la destreza y su indicador antes de generar el período.',
            'exists' => 'La selección curricular no es válida.',
            'duracion_minutos.multiple_of' => 'La duración debe corresponder a períodos de 45 minutos.',
            'enfoque.in' => 'Elige un campo válido del período.',
        ];
    }
}
