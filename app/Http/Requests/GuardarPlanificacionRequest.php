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

    /**
     * El selector de tema envía «taller» como valor especial; se traduce a la bandera es_taller.
     */
    protected function prepareForValidation(): void
    {
        $periodos = $this->input('periodos');
        if (! is_array($periodos)) {
            return;
        }

        foreach ($periodos as $indice => $periodo) {
            if (! is_array($periodo)) {
                continue;
            }
            $esTaller = ($periodo['tema_libro_id'] ?? null) === 'taller';
            $periodos[$indice]['es_taller'] = $esTaller;
            if ($esTaller) {
                $periodos[$indice]['tema_libro_id'] = null;
            } else {
                unset($periodos[$indice]['temas_taller']);
            }
        }

        $this->merge(['periodos' => $periodos]);
    }

    public function rules(): array
    {
        $planificacion = $this->route('planificacion');

        return [
            'asignacion_id' => ['required', 'integer', 'exists:asignaciones,id'],
            'unidad_libro_id' => ['nullable', 'integer', 'exists:unidades_libro,id'],
            'trimestre' => ['required', 'integer', 'between:1,3'],
            'titulo_unidad' => ['nullable', 'string', 'max:255'],
            'jornada' => ['prohibited'],
            'numero_semana' => ['required', 'integer', 'between:1,53', Rule::unique('planificaciones', 'numero_semana')
                ->where('asignacion_id', $this->input('asignacion_id'))
                ->ignore($planificacion instanceof Planificacion ? $planificacion->id : null)],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'numero_periodos' => ['prohibited'],
            'objetivo_curricular_ids' => ['nullable', 'array', 'max:10'],
            'objetivo_curricular_ids.*' => ['nullable', 'integer', 'distinct', 'exists:objetivos_curriculares,id'],
            'objetivos_aprendizaje' => ['prohibited'],
            'periodos' => ['required', 'array', 'min:1', 'max:40'],
            'periodos.*' => ['required', 'array'],
            'periodos.*.tema_libro_id' => ['nullable', 'integer', 'exists:temas_libro,id'],
            'periodos.*.es_taller' => ['boolean'],
            'periodos.*.temas_taller' => ['nullable', 'array', 'max:15'],
            'periodos.*.temas_taller.*' => ['integer', 'distinct', 'exists:temas_libro,id'],
            'periodos.*.destreza_curricular_id' => ['nullable', 'integer', 'exists:destrezas_curriculares,id'],
            'periodos.*.indicador_evaluacion_id' => ['nullable', 'integer', 'exists:indicadores_evaluacion,id'],
            'periodos.*.destrezas_criterios_desempeno' => ['prohibited'],
            'periodos.*.indicadores_evaluacion' => ['prohibited'],
            'periodos.*.recursos' => ['nullable', 'string', 'max:10000'],
            'periodos.*.tema' => ['nullable', 'string', 'max:5000'],
            'periodos.*.experiencia' => ['nullable', 'string', 'max:15000'],
            'periodos.*.dua_experiencia' => ['nullable', 'string', 'max:15000'],
            'periodos.*.reflexion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.dua_reflexion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.conceptualizacion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.dua_conceptualizacion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.aplicacion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.dua_aplicacion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.tecnica_evaluacion' => ['nullable', 'string', 'max:15000'],
            'periodos.*.instrumento_evaluacion' => ['nullable', 'string', 'max:15000'],
            'adaptacion_destreza' => ['nullable', 'string', 'max:15000'],
            'adaptacion_indicadores_evaluacion' => ['nullable', 'string', 'max:15000'],
            'adaptacion_recursos' => ['nullable', 'string', 'max:10000'],
            'adaptacion_orientaciones_metodologicas' => ['nullable', 'string', 'max:15000'],
            'adaptacion_actividades_evaluacion_formativa' => ['nullable', 'string', 'max:15000'],
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
            'unidad_libro_id.exists' => 'Selecciona una unidad válida para esta planificación.',
            'periodos.*.tema_libro_id.exists' => 'Selecciona un tema válido del libro.',
            'periodos.*.temas_taller.*.exists' => 'Selecciona temas válidos del libro para el taller.',
            'periodos.*.temas_taller.max' => 'Un taller puede reunir hasta 15 temas.',
            'numero_semana.unique' => 'Ya existe una planificación para esta asignación y semana.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'periodos.required' => 'La materia debe tener períodos configurados en su horario.',
            'numero_periodos.prohibited' => 'La cantidad de períodos se calcula automáticamente desde el horario.',
        ];
    }

    public function attributes(): array
    {
        return [
            'objetivo_curricular_ids' => 'objetivos de aprendizaje',
            'objetivo_curricular_ids.*' => 'objetivo de aprendizaje',
            'unidad_libro_id' => 'unidad del libro',
            'periodos.*.tema_libro_id' => 'tema de la unidad',
            'periodos.*.temas_taller' => 'temas del taller',
            'periodos.*.destreza_curricular_id' => 'destreza con criterios de desempeño',
            'periodos.*.indicador_evaluacion_id' => 'indicador de evaluación',
            'periodos.*.recursos' => 'recursos',
            'periodos.*.tema' => 'tema',
            'periodos.*.experiencia' => 'experiencia',
            'periodos.*.dua_experiencia' => 'DUA de experiencia',
            'periodos.*.reflexion' => 'reflexión',
            'periodos.*.dua_reflexion' => 'DUA de reflexión',
            'periodos.*.conceptualizacion' => 'conceptualización',
            'periodos.*.dua_conceptualizacion' => 'DUA de conceptualización',
            'periodos.*.aplicacion' => 'aplicación',
            'periodos.*.dua_aplicacion' => 'DUA de aplicación',
            'periodos.*.tecnica_evaluacion' => 'técnica de evaluación',
            'periodos.*.instrumento_evaluacion' => 'instrumento de evaluación',
            'adaptacion_indicadores_evaluacion' => 'indicadores de evaluación de la adaptación',
            'adaptacion_orientaciones_metodologicas' => 'orientaciones metodológicas de la adaptación',
            'adaptacion_actividades_evaluacion_formativa' => 'actividades e instrumentos de evaluación formativa',
        ];
    }
}
