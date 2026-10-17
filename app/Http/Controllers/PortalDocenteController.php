<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PortalDocenteController extends Controller
{
    public function index(Request $request): View
    {
        $asignaciones = $this->asignaciones($request->user()->docente_id);
        $materias = $asignaciones
            ->groupBy('materia_id')
            ->map(fn (Collection $grupo): array => [
                'materia' => $grupo->first()->materia,
                'asignaciones' => $grupo,
            ])
            ->sortBy(fn (array $grupo): string => $grupo['materia']->nombre);

        return view('PortalDocente.index', compact('materias'));
    }

    public function panel(Request $request): View
    {
        Gate::authorize('portal-docente');
        $asignaciones = $this->asignaciones($request->user()->docente_id, true);
        $secciones = $asignaciones->pluck('seccion')->unique('id')->values();
        $materias = $asignaciones->pluck('materia')->unique('id')->values();

        return view('PortalDocente.panel', compact('asignaciones', 'secciones', 'materias'));
    }

    private function asignaciones(int $docenteId, bool $soloPeriodosAbiertos = false): Collection
    {
        return Asignacion::with(['materia', 'seccion.curso', 'seccion.periodo'])
            ->withCount('planificaciones')
            ->where('docente_id', $docenteId)
            ->when($soloPeriodosAbiertos, fn ($consulta) => $consulta->whereHas('seccion.periodo', fn ($periodo) => $periodo->where('cerrado', false)))
            ->get()
            ->sortBy(fn (Asignacion $asignacion): array => [
                -$asignacion->seccion->periodo->fecha_inicio->timestamp,
                $asignacion->materia->nombre,
                $asignacion->seccion->curso->orden,
                $asignacion->seccion->nombre,
            ])
            ->values();
    }
}
