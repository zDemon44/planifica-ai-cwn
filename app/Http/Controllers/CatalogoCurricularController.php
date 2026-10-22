<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportarCatalogoCurricularRequest;
use App\Models\DestrezaCurricular;
use App\Models\Materia;
use App\Services\ImportarCatalogoCurricularExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogoCurricularController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('gestionar-docencia');
        $filtros = $request->validate([
            'materia_id' => ['nullable', 'integer', 'exists:materias,id'],
            'grado' => ['nullable', 'integer', Rule::in([2, 3, 4])],
        ]);
        $materias = Materia::where('estado', 'activo')->orderBy('nombre')->get();
        $materiaId = (int) ($filtros['materia_id'] ?? $materias->first()?->id);
        $grado = (int) ($filtros['grado'] ?? 4);
        $destrezas = DestrezaCurricular::with(['competencias', 'indicadores' => fn ($consulta) => $consulta->where('activo', true)->orderBy('codigo'), 'indicadores.competencias'])
            ->where('materia_id', $materiaId)->where('grado', $grado)->where('activo', true)
            ->orderBy('grupo')->orderBy('codigo')->get();

        return view('CatalogoCurricular.index', compact('materias', 'materiaId', 'grado', 'destrezas'));
    }

    public function importar(ImportarCatalogoCurricularRequest $request, ImportarCatalogoCurricularExcel $importador): RedirectResponse
    {
        $materia = Materia::findOrFail($request->integer('materia_id'));
        $resultado = $importador->handle($materia, $request->file('archivo'));

        return redirect()->route('catalogo-curricular.index', ['materia_id' => $materia->id, 'grado' => 4])
            ->with('success', "Catálogo importado: {$resultado['destrezas']} destrezas, {$resultado['indicadores']} indicadores, {$resultado['relaciones']} relaciones y {$resultado['competencias']} competencias.");
    }
}
