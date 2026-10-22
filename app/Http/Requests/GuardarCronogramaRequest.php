<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GuardarCronogramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('gestionar-docencia');
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after:fecha_inicio'],
            'trimestre_1_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio', 'before:trimestre_2_fin'],
            'trimestre_2_fin' => ['required', 'date_format:Y-m-d', 'after:trimestre_1_fin', 'before:trimestre_3_fin'],
            'trimestre_3_fin' => ['required', 'date_format:Y-m-d', 'after:trimestre_2_fin', 'same:fecha_fin'],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'Completa este campo.', 'after' => 'La fecha debe ser posterior.', 'before' => 'Los cierres trimestrales deben estar en orden.', 'same' => 'El tercer trimestre debe finalizar junto con el año lectivo.'];
    }
}
