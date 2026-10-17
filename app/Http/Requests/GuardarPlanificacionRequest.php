<?php

namespace App\Http\Requests;

use App\Models\Planificacion;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarPlanificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planificacion = $this->route('planificacion');

        return $planificacion instanceof Planificacion
            ? Gate::allows('update', $planificacion)
            : Gate::allows('create', Planificacion::class);
    }

    public function rules(): array
    {
        $planificacion = $this->route('planificacion');

        return [
            'asignacion_id' => ['required', 'integer', 'exists:asignaciones,id'],
            'trimestre' => ['required', 'integer', 'between:1,3'],
            'numero_semana' => ['required', 'integer', 'between:1,53', Rule::unique('planificaciones', 'numero_semana')
                ->where('asignacion_id', $this->input('asignacion_id'))
                ->ignore($planificacion instanceof Planificacion ? $planificacion->id : null)],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'numero_periodos' => ['prohibited'],
            'area' => ['nullable', 'string', 'max:120'],
            'objetivo_disciplinar' => ['nullable', 'string', 'max:5000'],
            'estandar_aprendizaje' => ['nullable', 'string', 'max:5000'],
            'periodos' => ['required', 'array', 'min:1', 'max:40'],
            'periodos.*' => ['required', 'array'],
            'periodos.*.destreza_desempeno' => ['nullable', 'string', 'max:5000'],
            'periodos.*.indicadores_logro' => ['nullable', 'string', 'max:5000'],
            'periodos.*.recursos' => ['nullable', 'string', 'max:5000'],
            'periodos.*.tema' => ['nullable', 'string', 'max:5000'],
            'periodos.*.anticipacion' => ['nullable', 'string', 'max:10000'],
            'periodos.*.construccion' => ['nullable', 'string', 'max:10000'],
            'periodos.*.consolidacion' => ['nullable', 'string', 'max:10000'],
            'periodos.*.tecnicas_evaluacion' => ['nullable', 'string', 'max:5000'],
            'adaptacion_especificacion' => ['nullable', 'string', 'max:5000'],
            'adaptacion_destreza' => ['nullable', 'string', 'max:5000'],
            'adaptacion_indicadores' => ['nullable', 'string', 'max:5000'],
            'adaptacion_recursos' => ['nullable', 'string', 'max:5000'],
            'adaptacion_estrategias' => ['nullable', 'string', 'max:5000'],
            'adaptacion_evaluacion' => ['nullable', 'string', 'max:5000'],
            'version' => [$planificacion instanceof Planificacion ? 'required' : 'prohibited', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['fecha_inicio', 'fecha_fin'])) {
                    return;
                }

                if (Carbon::parse($this->input('fecha_fin'))->gt(Carbon::parse($this->input('fecha_inicio'))->addDays(6))) {
                    $validator->errors()->add('fecha_fin', 'La planificación semanal no puede abarcar más de siete días.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.',
            'exists' => 'Selecciona una asignación válida.',
            'numero_semana.unique' => 'Ya existe una planificación para esta asignación y semana.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'periodos.required' => 'La materia debe tener períodos configurados en su horario.',
            'numero_periodos.prohibited' => 'La cantidad de períodos se calcula automáticamente desde el horario.',
        ];
    }

    public function attributes(): array
    {
        return [
            'periodos.*.destreza_desempeno' => 'destreza con criterio de desempeño',
            'periodos.*.indicadores_logro' => 'indicadores de logro',
            'periodos.*.recursos' => 'recursos',
            'periodos.*.tema' => 'tema',
            'periodos.*.anticipacion' => 'anticipación',
            'periodos.*.construccion' => 'construcción',
            'periodos.*.consolidacion' => 'consolidación',
            'periodos.*.tecnicas_evaluacion' => 'técnicas e instrumentos de evaluación',
        ];
    }
}
