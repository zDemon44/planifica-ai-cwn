<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ImportarCatalogoCurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('gestionar-docencia');
    }

    public function rules(): array
    {
        return [
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona el archivo Excel.',
            'archivo.mimes' => 'El catálogo debe estar en formato .xlsx.',
            'archivo.max' => 'El archivo no puede superar 5 MB.',
            'materia_id.required' => 'Selecciona la materia del catálogo.',
        ];
    }
}
