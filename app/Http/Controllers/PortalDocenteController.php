<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    private function asignaciones(int $docenteId): Collection
    {
        return Asignacion::with(['materia', 'seccion.curso', 'seccion.periodo'])
            ->withCount('planificaciones')
            ->where('docente_id', $docenteId)
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
