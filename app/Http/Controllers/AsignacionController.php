<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarAsignacionRequest;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AsignacionController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['docente_id' => ['nullable', 'integer', 'exists:docentes,id'], 'periodo_id' => ['nullable', 'integer', 'exists:periodos_academicos,id']]);
        $asignaciones = Asignacion::with(['docente', 'materia', 'seccion.curso', 'seccion.periodo'])
            ->when($filtros['docente_id'] ?? null, fn ($consulta, $id) => $consulta->where('docente_id', $id))
            ->when($filtros['periodo_id'] ?? null, fn ($consulta, $id) => $consulta->whereHas('seccion', fn ($seccion) => $seccion->where('periodo_academico_id', $id)))
            ->orderByDesc('id')->paginate(10)->withQueryString();

        return view('Asignaciones.index', ['asignaciones' => $asignaciones, 'filtros' => $filtros, 'docentes' => Docente::orderBy('apellidos')->get(), 'periodos' => PeriodoAcademico::orderByDesc('fecha_inicio')->get()]);
    }

    public function create(): View
    {
        return $this->formulario(new Asignacion);
    }

    public function edit(Asignacion $asignacion): View
    {
        $asignacion->load('seccion.periodo');
        abort_if($asignacion->seccion->periodo->cerrado, 403, 'El período está cerrado.');
        abort_if($asignacion->planificaciones()->exists(), 403, 'La asignación ya tiene planificaciones y se conserva como historial.');

        return $this->formulario($asignacion);
    }

    private function formulario(Asignacion $asignacion): View
    {
        return view('Asignaciones.form', [
            'asignacion' => $asignacion,
            'docentes' => Docente::where('estado', 'activo')->orderBy('apellidos')->get(),
            'materias' => Materia::where('estado', 'activo')->orderBy('nombre')->get(),
            'secciones' => Seccion::with(['curso', 'periodo'])->whereHas('periodo', fn ($consulta) => $consulta->where('cerrado', false))->orderByDesc('periodo_academico_id')->orderBy('curso_id')->orderBy('nombre')->get(),
        ]);
    }

    public function show(Asignacion $asignacion): View
    {
        $asignacion->load(['docente', 'materia', 'seccion.curso', 'seccion.periodo']);
        $asignacion->loadCount('planificaciones');

        return view('Asignaciones.show', compact('asignacion'));
    }

    public function store(GuardarAsignacionRequest $request): RedirectResponse
    {
        $asignacion = $this->guardar($request->validated());

        return redirect()->route('asignaciones.show', $asignacion)->with('success', 'Asignación creada correctamente.');
    }

    public function update(GuardarAsignacionRequest $request, Asignacion $asignacion): RedirectResponse
    {
        $asignacion = $this->guardar($request->validated(), $asignacion);

        return redirect()->route('asignaciones.show', $asignacion)->with('success', 'Asignación actualizada correctamente.');
    }

    private function guardar(array $datos, ?Asignacion $asignacion = null): Asignacion
    {
        return DB::transaction(function () use ($datos, $asignacion) {
            $destino = Seccion::findOrFail($datos['seccion_id']);
            $periodos = [$destino->periodo_academico_id];
            if ($asignacion) {
                $periodos[] = $asignacion->seccion->periodo_academico_id;
            }
            $bloqueados = PeriodoAcademico::whereIn('id', $periodos)->orderBy('id')->lockForUpdate()->get();
            if ($bloqueados->contains('cerrado', true)) {
                throw ValidationException::withMessages(['seccion_id' => 'No puedes modificar asignaciones de un período cerrado.']);
            }
            $seccion = Seccion::lockForUpdate()->findOrFail($destino->id);
            if (! $bloqueados->contains('id', $seccion->periodo_academico_id)) {
                throw ValidationException::withMessages(['seccion_id' => 'El período cambió. Vuelve a seleccionar el paralelo.']);
            }
            $docente = Docente::lockForUpdate()->findOrFail($datos['docente_id']);
            $materia = Materia::lockForUpdate()->findOrFail($datos['materia_id']);
            if ($docente->estado !== 'activo' || $materia->estado !== 'activo') {
                throw ValidationException::withMessages(['docente_id' => 'Selecciona un docente y una materia activos.']);
            }
            if ($asignacion) {
                $registro = Asignacion::lockForUpdate()->findOrFail($asignacion->id);
                if ($registro->seccion_id !== $asignacion->seccion_id) {
                    throw ValidationException::withMessages(['seccion_id' => 'La asignación cambió. Recarga la página.']);
                }
                if ($registro->planificaciones()->exists() && (
                    $registro->docente_id !== (int) $datos['docente_id']
                    || $registro->materia_id !== (int) $datos['materia_id']
                    || $registro->seccion_id !== (int) $datos['seccion_id']
                )) {
                    throw ValidationException::withMessages(['seccion_id' => 'La asignación tiene planificaciones. Conserva este historial y crea otra asignación.']);
                }
            } else {
                $registro = new Asignacion;
            }
            if (Asignacion::where($datos)->where('id', '!=', $registro->id ?? 0)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['seccion_id' => 'Este docente ya tiene esta materia asignada al mismo curso y paralelo.']);
            }
            $registro->fill($datos)->save();

            return $registro;
        }, 3);
    }

    public function destroy(Asignacion $asignacion): RedirectResponse
    {
        DB::transaction(function () use ($asignacion) {
            $periodo = PeriodoAcademico::lockForUpdate()->findOrFail($asignacion->seccion->periodo_academico_id);
            $registro = Asignacion::lockForUpdate()->findOrFail($asignacion->id);
            if ($periodo->cerrado || $registro->seccion_id !== $asignacion->seccion_id) {
                throw ValidationException::withMessages(['registro' => 'No se puede eliminar: el período está cerrado o la asignación cambió.']);
            }
            if ($registro->planificaciones()->exists()) {
                throw ValidationException::withMessages(['registro' => 'No se puede eliminar una asignación con planificaciones.']);
            }
            $registro->delete();
        }, 3);

        return redirect()->route('asignaciones.index')->with('success', 'Asignación eliminada.');
    }
}
