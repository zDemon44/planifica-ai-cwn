<?php

namespace App\Http\Requests;

use App\Models\Horario;
use App\Models\Recreo;
use App\Models\Seccion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarHorarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $horario = $this->route('horario');

        return [
            'docente_id' => ['required', 'integer', Rule::exists('docentes', 'id')->where('estado', 'activo')],
            'seccion_id' => ['required', 'integer', Rule::exists('secciones', 'id')],
            'version' => [$horario instanceof Horario ? 'required' : 'nullable', 'integer', 'min:1'],
            'bloques' => ['required', 'array', 'min:1', 'max:50'],
            'bloques.*.materia_id' => ['required', 'integer', Rule::exists('materias', 'id')->where('estado', 'activo')],
            'bloques.*.dia_semana' => ['required', 'integer', Rule::in(array_keys(Horario::DIAS))],
            'bloques.*.hora_inicio' => ['required', 'date_format:H:i'],
            'bloques.*.hora_fin' => ['required', 'date_format:H:i', 'after:bloques.*.hora_inicio'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $bloques = $this->input('bloques', []);
            foreach ($bloques as $indice => $bloque) {
                foreach (array_slice($bloques, 0, $indice) as $anterior) {
                    if (($bloque['dia_semana'] ?? null) !== ($anterior['dia_semana'] ?? null)) {
                        continue;
                    }
                    if (($bloque['hora_inicio'] ?? '') < ($anterior['hora_fin'] ?? '') && ($bloque['hora_fin'] ?? '') > ($anterior['hora_inicio'] ?? '')) {
                        $validator->errors()->add("bloques.$indice.hora_inicio", 'Este bloque se cruza con otro del mismo día.');
                    }
                }
            }

            if ($validator->errors()->hasAny(['seccion_id', 'bloques'])) {
                return;
            }

            $seccion = Seccion::with('curso')->find($this->integer('seccion_id'));
            if (! $seccion?->curso->subnivel) {
                return;
            }
            $recreo = Recreo::where('periodo_academico_id', $seccion->periodo_academico_id)
                ->where('subnivel', $seccion->curso->subnivel)->where('activo', true)->first();
            if (! $recreo) {
                return;
            }
            $horaInicioRecreo = substr($recreo->hora_inicio, 0, 5);
            $horaFinRecreo = substr($recreo->hora_fin, 0, 5);

            foreach ($bloques as $indice => $bloque) {
                if (! in_array((int) ($bloque['dia_semana'] ?? 0), $recreo->dias_semana, true)) {
                    continue;
                }
                if (($bloque['hora_inicio'] ?? '') < $horaFinRecreo && ($bloque['hora_fin'] ?? '') > $horaInicioRecreo) {
                    $validator->errors()->add("bloques.$indice.hora_inicio", "Este bloque se cruza con {$recreo->nombre} ({$horaInicioRecreo}–{$horaFinRecreo}).");
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'docente_id' => 'docente responsable',
            'seccion_id' => 'curso y paralelo',
            'bloques.*.materia_id' => 'materia',
            'bloques.*.dia_semana' => 'día',
            'bloques.*.hora_inicio' => 'hora de inicio',
            'bloques.*.hora_fin' => 'hora de fin',
        ];
    }
}
