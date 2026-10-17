<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMateriaRequest;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MateriaController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['busqueda' => ['nullable', 'string', 'max:100']]);
        $busqueda = trim($datos['busqueda'] ?? '');
        $materias = Materia::withCount('asignaciones')->when($busqueda !== '', function ($consulta) use ($busqueda) {
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('nombre', 'like', '%'.$busqueda.'%');
            });
        })->orderBy('nombre')->orderBy('id')->paginate(10)->withQueryString();

        return view('Materias.index', compact('materias', 'busqueda'));
    }

    public function create(): View
    {
        return view('Materias.form', ['materia' => new Materia]);
    }

    public function store(GuardarMateriaRequest $request): RedirectResponse
    {
        $materia = Materia::create($request->validated());

        return redirect()->route('materias.show', $materia)->with('success', 'Registro creado correctamente.');
    }

    public function show(Materia $materia): View
    {
        $asignaciones = $materia->asignaciones()->with(['docente', 'materia', 'seccion.curso', 'seccion.periodo'])->orderByDesc('id')->paginate(10);

        return view('Materias.show', compact('materia', 'asignaciones'));
    }

    public function edit(Materia $materia): View
    {
        return view('Materias.form', compact('materia'));
    }

    public function update(GuardarMateriaRequest $request, Materia $materia): RedirectResponse
    {
        DB::transaction(function () use ($request, $materia) {
            $registro = Materia::lockForUpdate()->findOrFail($materia->id);
            $registro->update($request->validated());
        });

        return redirect()->route('materias.show', $materia)->with('success', 'Datos actualizados correctamente.');
    }

    public function destroy(Materia $materia): RedirectResponse
    {
        DB::transaction(function () use ($materia) {
            $registro = Materia::lockForUpdate()->findOrFail($materia->id);
            if ($registro->asignaciones()->exists()) {
                throw ValidationException::withMessages(['registro' => 'Este registro tiene asignaciones. Cambia su estado a inactivo para conservar el historial.']);
            }
            $registro->delete();
        });

        return redirect()->route('materias.index')->with('success', 'Registro eliminado.');
    }
}
