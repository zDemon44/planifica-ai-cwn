<?php

namespace App\Http\Controllers;

use App\Actions\ConstruirPeriodosPlanificacion;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\PeriodoAcademico;
use App\Models\Planificacion;
use App\Models\Seccion;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function __construct(private ConstruirPeriodosPlanificacion $construirPeriodos) {}

    public function index(Request $request): View
    {
        return $request->user()->esDocente()
            ? $this->panelDocente($request)
            : $this->panelAdministrativo($request->user()->role === 'SUPERADMIN');
    }

    private function panelAdministrativo(bool $esSuperadmin): View
    {
        $periodo = PeriodoAcademico::withCount(['cierresTrimestrales', 'diasNoClase'])
            ->where('cerrado', false)
            ->orderByDesc('fecha_inicio')
            ->first();

        $planificacionesDelPeriodo = fn (): Builder => Planificacion::query()
            ->when($periodo, fn (Builder $consulta) => $consulta->whereHas('asignacion.seccion', fn (Builder $secciones) => $secciones->where('periodo_academico_id', $periodo->id)));

        $conteoEstados = $planificacionesDelPeriodo()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');
        $estados = collect(Planificacion::ESTADOS)->map(fn (string $etiqueta, string $clave): int => (int) ($conteoEstados[$clave] ?? 0));

        $pendientes = $planificacionesDelPeriodo()
            ->with(['asignacion.docente', 'asignacion.materia', 'asignacion.seccion.curso'])
            ->whereIn('estado', ['en_revision', 'revisada'])
            ->orderByRaw('enviado_en IS NULL, enviado_en')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $seccionesTotales = $periodo ? Seccion::where('periodo_academico_id', $periodo->id)->count() : 0;
        $seccionesConHorario = $periodo ? Seccion::where('periodo_academico_id', $periodo->id)->has('horario')->count() : 0;
        $totalAsignaciones = $periodo ? Asignacion::whereHas('seccion', fn (Builder $secciones) => $secciones->where('periodo_academico_id', $periodo->id))->count() : 0;
        $docentesSinCuenta = Docente::where('estado', 'activo')->doesntHave('usuario')->count();
        $configuracion = [
            [
                'titulo' => 'Cronograma y trimestres',
                'detalle' => $periodo ? "{$periodo->cierres_trimestrales_count} de 3 cierres trimestrales · {$periodo->dias_no_clase_count} suspensiones registradas" : 'No existe un período académico abierto',
                'completo' => $periodo && $periodo->cierres_trimestrales_count >= 3,
                'ruta' => route('cronogramas.index'),
            ],
            [
                'titulo' => 'Horarios por curso',
                'detalle' => "{$seccionesConHorario} de {$seccionesTotales} cursos con horario",
                'completo' => $seccionesTotales > 0 && $seccionesConHorario === $seccionesTotales,
                'ruta' => route('horarios.index'),
            ],
            [
                'titulo' => 'Asignaciones docentes',
                'detalle' => "{$totalAsignaciones} asignaciones en el período",
                'completo' => $totalAsignaciones > 0,
                'ruta' => route('asignaciones.index'),
            ],
            [
                'titulo' => 'Cuentas de docentes',
                'detalle' => $docentesSinCuenta === 0 ? 'Todos los docentes activos tienen cuenta' : "{$docentesSinCuenta} docentes activos sin cuenta",
                'completo' => $docentesSinCuenta === 0,
                'ruta' => route($esSuperadmin ? 'usuarios.index' : 'docentes.index'),
            ],
        ];

        return view('panel', [
            'periodo' => $periodo,
            'estados' => $estados,
            'totalPlanificaciones' => $estados->sum(),
            'pendientes' => $pendientes,
            'totalPendientes' => $estados['en_revision'] + $estados['revisada'],
            'configuracion' => $configuracion,
        ]);
    }

    private function panelDocente(Request $request): View
    {
        Gate::authorize('portal-docente');
        $docenteId = $request->user()->docente_id;
        $asignaciones = Asignacion::with(['materia', 'seccion.curso', 'seccion.periodo'])
            ->withCount([
                'planificaciones',
                'planificaciones as aprobadas_count' => fn (Builder $consulta) => $consulta->where('estado', 'aprobada'),
            ])
            ->where('docente_id', $docenteId)
            ->whereHas('seccion.periodo', fn (Builder $periodo) => $periodo->where('cerrado', false))
            ->get()
            ->sortBy(fn (Asignacion $asignacion): array => [
                -$asignacion->seccion->periodo->fecha_inicio->timestamp,
                $asignacion->materia->nombre,
                $asignacion->seccion->curso->orden,
                $asignacion->seccion->nombre,
            ])
            ->values();
        $secciones = $asignaciones->pluck('seccion')->unique('id')->values();
        $materias = $asignaciones->pluck('materia')->unique('id')->values();

        $lunes = $this->lunesDeTrabajo();
        $semana = $this->semanaDocente($asignaciones, $lunes);

        $devueltas = Planificacion::with(['asignacion.materia', 'asignacion.seccion.curso'])
            ->whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->where('estado', 'devuelta')
            ->orderByDesc('revisado_en')
            ->orderByDesc('id')
            ->get();

        $enRevision = Planificacion::whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereIn('estado', ['en_revision', 'revisada'])
            ->count();

        return view('PortalDocente.panel', compact('asignaciones', 'secciones', 'materias', 'lunes', 'semana', 'devueltas', 'enRevision'));
    }

    /**
     * Semana que el docente debe tener lista: la actual de lunes a viernes, o la siguiente durante el fin de semana.
     */
    private function lunesDeTrabajo(): Carbon
    {
        $hoy = Carbon::today();
        $lunes = $hoy->copy()->startOfWeek(CarbonInterface::MONDAY);

        return $hoy->isWeekend() ? $lunes->addWeek() : $lunes;
    }

    /**
     * @param  Collection<int, Asignacion>  $asignaciones
     * @return Collection<int, array{asignacion: Asignacion, periodos: int, inicio: string, planificacion: ?Planificacion}>
     */
    private function semanaDocente(Collection $asignaciones, Carbon $lunes): Collection
    {
        $viernes = $lunes->copy()->addDays(4);
        $planificaciones = Planificacion::whereIn('asignacion_id', $asignaciones->pluck('id'))
            ->whereBetween('fecha_inicio', [$lunes->toDateString(), $viernes->toDateString()])
            ->get()
            ->keyBy('asignacion_id');

        return $asignaciones
            ->filter(fn (Asignacion $asignacion): bool => $lunes->lte($asignacion->seccion->periodo->fecha_fin) && $viernes->gte($asignacion->seccion->periodo->fecha_inicio))
            ->map(fn (Asignacion $asignacion): array => [
                'asignacion' => $asignacion,
                'periodos' => $this->construirPeriodos->handle($asignacion, $lunes)->sum(fn (array $bloque): int => $bloque['numero_fin'] - $bloque['numero_inicio'] + 1),
                'inicio' => $lunes->copy()->max($asignacion->seccion->periodo->fecha_inicio)->toDateString(),
                'planificacion' => $planificaciones->get($asignacion->id),
            ])
            ->filter(fn (array $fila): bool => $fila['periodos'] > 0 || $fila['planificacion'])
            ->values();
    }
}
