<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\DestrezaCurricular;
use App\Models\Materia;
use App\Models\PeriodoPlanificacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeguimientoCurricularController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('ver-seguimiento-curricular');
        $filtros = $request->validate([
            'materia_id' => ['nullable', 'integer', 'exists:materias,id'],
            'grado' => ['nullable', 'integer', Rule::in(range(1, 10))],
        ]);
        $opciones = $this->opcionesDisponibles($request);
        $seleccion = $opciones->first(fn (array $opcion): bool => (! isset($filtros['materia_id']) || $opcion['materia_id'] === (int) $filtros['materia_id'])
            && (! isset($filtros['grado']) || $opcion['grado'] === (int) $filtros['grado'])
        );
        if (($filtros['materia_id'] ?? null) || ($filtros['grado'] ?? null)) {
            abort_unless($seleccion, 404);
        }
        $seleccion ??= $opciones->first();
        $materia = $seleccion ? Materia::find($seleccion['materia_id']) : null;
        $destrezas = collect();
        $usosDestrezas = collect();
        $usosIndicadores = collect();

        if ($seleccion) {
            $destrezas = DestrezaCurricular::with(['competencias', 'indicadores' => fn ($consulta) => $consulta
                ->where('indicadores_evaluacion.activo', true)->orderBy('grupo')->orderBy('codigo'), 'indicadores.competencias'])
                ->where('materia_id', $seleccion['materia_id'])->where('grado', $seleccion['grado'])
                ->where('activo', true)->orderBy('grupo')->orderBy('codigo')->get();
            $usosDestrezas = $this->conteos($request, $seleccion, 'destreza_curricular_id');
            $usosIndicadores = $this->conteos($request, $seleccion, 'indicador_evaluacion_id');
        }

        return view('SeguimientoCurricular.index', compact(
            'opciones', 'seleccion', 'materia', 'destrezas', 'usosDestrezas', 'usosIndicadores'
        ));
    }

    /** @return Collection<int, array{materia_id:int, materia:string, grado:int}> */
    private function opcionesDisponibles(Request $request): Collection
    {
        if ($request->user()->esDocente()) {
            return Asignacion::with(['materia', 'seccion.curso'])
                ->where('docente_id', $request->user()->docente_id)->get()
                ->filter(fn (Asignacion $asignacion): bool => (bool) $asignacion->seccion->curso->grado)
                ->map(fn (Asignacion $asignacion): array => [
                    'materia_id' => $asignacion->materia_id,
                    'materia' => $asignacion->materia->nombre,
                    'grado' => $asignacion->seccion->curso->grado,
                ])->unique(fn (array $opcion): string => $opcion['materia_id'].'-'.$opcion['grado'])
                ->sortBy(fn (array $opcion): string => $opcion['materia'].'-'.$opcion['grado'])->values();
        }

        return DestrezaCurricular::query()->with('materia')->where('activo', true)->get()
            ->map(fn (DestrezaCurricular $destreza): array => [
                'materia_id' => $destreza->materia_id,
                'materia' => $destreza->materia->nombre,
                'grado' => $destreza->grado,
            ])->unique(fn (array $opcion): string => $opcion['materia_id'].'-'.$opcion['grado'])
            ->sortBy(fn (array $opcion): string => $opcion['materia'].'-'.$opcion['grado'])->values();
    }

    /** @param array{materia_id:int, materia:string, grado:int} $seleccion */
    private function conteos(Request $request, array $seleccion, string $columna): Collection
    {
        return PeriodoPlanificacion::query()
            ->select($columna, DB::raw('COUNT(*) AS usos'), DB::raw('COUNT(DISTINCT periodos_planificacion.planificacion_id) AS planificaciones'))
            ->join('planificaciones', 'planificaciones.id', '=', 'periodos_planificacion.planificacion_id')
            ->join('asignaciones', 'asignaciones.id', '=', 'planificaciones.asignacion_id')
            ->join('secciones', 'secciones.id', '=', 'asignaciones.seccion_id')
            ->join('cursos', 'cursos.id', '=', 'secciones.curso_id')
            ->where('asignaciones.materia_id', $seleccion['materia_id'])
            ->where('cursos.grado', $seleccion['grado'])
            ->whereNotNull($columna)
            ->when($request->user()->esDocente(), fn (Builder $consulta) => $consulta
                ->where('asignaciones.docente_id', $request->user()->docente_id))
            ->groupBy($columna)->get()->keyBy($columna);
    }
}
