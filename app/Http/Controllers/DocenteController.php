<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarDocenteRequest;
use App\Models\Docente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocenteController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['busqueda' => ['nullable', 'string', 'max:100']]);
        $busqueda = trim($datos['busqueda'] ?? '');
        $docentes = Docente::withCount('asignaciones')->when($busqueda !== '', function ($consulta) use ($busqueda) {
            $consulta->where(function ($consulta) use ($busqueda) {
                $consulta->where('nombres', 'like', '%'.$busqueda.'%')->orWhere('apellidos', 'like', '%'.$busqueda.'%')->orWhere('identificacion', 'like', '%'.$busqueda.'%');
            });
        })->orderBy('apellidos')->orderBy('id')->paginate(10)->withQueryString();

        return view('Docentes.index', compact('docentes', 'busqueda'));
    }

    public function create(): View
    {
        return view('Docentes.form', ['docente' => new Docente]);
    }

    public function store(GuardarDocenteRequest $request): RedirectResponse
    {
        $docente = Docente::create($request->validated());

        return redirect()->route('docentes.show', $docente)->with('success', 'Registro creado correctamente.');
    }

    public function show(Docente $docente): View
    {
        $asignaciones = $docente->asignaciones()->with(['docente', 'materia', 'seccion.curso', 'seccion.periodo'])->orderByDesc('id')->paginate(10);

        return view('Docentes.show', compact('docente', 'asignaciones'));
    }

    public function edit(Docente $docente): View
    {
        return view('Docentes.form', compact('docente'));
    }

    public function update(GuardarDocenteRequest $request, Docente $docente): RedirectResponse
    {
        DB::transaction(function () use ($request, $docente) {
            $registro = Docente::lockForUpdate()->findOrFail($docente->id);
            $registro->update($request->validated());
        });

        return redirect()->route('docentes.show', $docente)->with('success', 'Datos actualizados correctamente.');
    }

    public function destroy(Docente $docente): RedirectResponse
    {
        DB::transaction(function () use ($docente) {
            $registro = Docente::lockForUpdate()->findOrFail($docente->id);
            if ($registro->asignaciones()->exists()) {
                throw ValidationException::withMessages(['registro' => 'Este registro tiene asignaciones. Cambia su estado a inactivo para conservar el historial.']);
            }
            if ($registro->usuario()->exists()) {
                throw ValidationException::withMessages(['registro' => 'Este docente tiene una cuenta vinculada. Cambia su estado a inactivo para conservar el historial.']);
            }
            $registro->delete();
        });

        return redirect()->route('docentes.index')->with('success', 'Registro eliminado.');
    }
}
