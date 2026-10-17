<?php

namespace App\Http\Requests;

use App\Models\BloqueHorario;
use App\Models\Curso;
use App\Models\Horario;
use App\Models\Seccion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarRecreoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-docencia') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periodo_academico_id' => ['required', 'integer', Rule::exists('periodos_academicos', 'id')],
            'subnivel' => ['required', 'string', Rule::in(array_keys(Curso::SUBNIVELES)), Rule::unique('recreos')->where('periodo_academico_id', $this->integer('periodo_academico_id'))->ignore($this->route('recreo'))],
            'nombre' => ['required', 'string', 'max:60'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['required', 'integer', Rule::in(array_keys(Horario::DIAS)), 'distinct'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['periodo_academico_id', 'subnivel', 'hora_inicio', 'hora_fin', 'dias_semana'])) {
                return;
            }
            if (! $this->boolean('activo')) {
                return;
            }

            $secciones = Seccion::where('periodo_academico_id', $this->integer('periodo_academico_id'))
                ->whereHas('curso', fn ($consulta) => $consulta->where('subnivel', $this->string('subnivel')->toString()))
                ->pluck('id');

            $hayCruce = BloqueHorario::whereHas('horario', fn ($consulta) => $consulta->whereIn('seccion_id', $secciones))
                ->whereIn('dia_semana', $this->input('dias_semana', []))
                ->where('hora_inicio', '<', $this->input('hora_fin'))
                ->where('hora_fin', '>', $this->input('hora_inicio'))
                ->exists();

            if ($hayCruce) {
                $validator->errors()->add('hora_inicio', 'El recreo se cruza con una clase existente de este subnivel. Ajusta primero ese horario.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'periodo_academico_id' => 'período académico',
            'subnivel' => 'subnivel',
            'nombre' => 'nombre',
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
            'dias_semana' => 'días de la semana',
        ];
    }
}
