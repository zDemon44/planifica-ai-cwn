<?php

namespace App\Http\Controllers;

use App\Actions\ConstruirPeriodosPlanificacion;
use App\Http\Requests\GuardarPlanificacionRequest;
use App\Models\Asignacion;
use App\Models\Horario;
use App\Models\PeriodoAcademico;
use App\Models\Planificacion;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanificacionController extends Controller
{
    public function __construct(private ConstruirPeriodosPlanificacion $construirPeriodos) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Planificacion::class);
        $filtros = $request->validate([
            'estado' => ['nullable', Rule::in(array_keys(Planificacion::ESTADOS))],
            'periodo_id' => ['nullable', 'integer', 'exists:periodos_academicos,id'],
            'asignacion_id' => ['nullable', 'integer', 'exists:asignaciones,id'],
        ]);

        $usuario = $request->user();
        $asignaciones = collect();
        $asignacionSeleccionada = null;
        if ($usuario->esDocente()) {
            $asignaciones = Asignacion::with(['materia', 'seccion.curso', 'seccion.periodo'])
                ->withCount('planificaciones')
                ->where('docente_id', $usuario->docente_id)
                ->get()
                ->sortBy(fn (Asignacion $asignacion): array => [-$asignacion->seccion->periodo->fecha_inicio->timestamp, $asignacion->materia->nombre, $asignacion->seccion->curso->orden])
                ->values();

            if ($filtros['asignacion_id'] ?? null) {
                $asignacionSeleccionada = $asignaciones->firstWhere('id', (int) $filtros['asignacion_id']);
                abort_unless($asignacionSeleccionada, 404);
            }
        }

        $planificaciones = Planificacion::with(['asignacion.docente', 'asignacion.materia', 'asignacion.seccion.curso', 'asignacion.seccion.periodo'])
            ->when($usuario->esDocente(), fn ($consulta) => $consulta->whereHas('asignacion', fn ($asignaciones) => $asignaciones->where('docente_id', $usuario->docente_id)))
            ->when($asignacionSeleccionada, fn ($consulta) => $consulta->where('asignacion_id', $asignacionSeleccionada->id))
            ->when($filtros['estado'] ?? null, fn ($consulta, $estado) => $consulta->where('estado', $estado))
            ->when($filtros['periodo_id'] ?? null, fn ($consulta, $periodoId) => $consulta->whereHas('asignacion.seccion', fn ($secciones) => $secciones->where('periodo_academico_id', $periodoId)))
            ->orderByDesc('fecha_inicio')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('Planificaciones.index', [
            'planificaciones' => $planificaciones,
            'periodos' => PeriodoAcademico::orderByDesc('fecha_inicio')->get(),
            'filtros' => $filtros,
            'asignaciones' => $asignaciones,
            'asignacionSeleccionada' => $asignacionSeleccionada,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Planificacion::class);
        $datos = $request->validate(['asignacion_id' => ['nullable', 'integer', 'exists:asignaciones,id']]);

        return $this->formulario(new Planificacion, $request, isset($datos['asignacion_id']) ? (int) $datos['asignacion_id'] : null);
    }

    public function edit(Planificacion $planificacion, Request $request): View
    {
        Gate::authorize('update', $planificacion);

        return $this->formulario($planificacion, $request);
    }

    private function formulario(Planificacion $planificacion, Request $request, ?int $asignacionId = null): View
    {
        $asignaciones = Asignacion::with(['materia', 'seccion.curso', 'seccion.periodo'])
            ->where('docente_id', $request->user()->docente_id)
            ->whereHas('seccion.periodo', fn ($consulta) => $consulta->where('cerrado', false))
            ->orderBy('id')->get();

        $resumenHorarios = $asignaciones->mapWithKeys(function (Asignacion $asignacion): array {
            $bloques = $this->construirPeriodos->handle($asignacion);

            return [$asignacion->id => [
                'periodos' => $bloques->sum(fn (array $bloque): int => $bloque['numero_fin'] - $bloque['numero_inicio'] + 1),
                'detalle' => $bloques->map(fn (array $bloque): string => $bloque['dia'].' '.$bloque['hora_inicio'].'–'.$bloque['hora_fin'])->implode(' · '),
                'bloques' => $bloques->all(),
            ]];
        });
        $asignacionSeleccionada = $planificacion->exists
            ? $planificacion->asignacion->load(['materia', 'seccion.curso', 'seccion.periodo'])
            : ($asignacionId ? $asignaciones->firstWhere('id', $asignacionId) : null);
        abort_if($asignacionId && ! $asignacionSeleccionada, 404);

        $bloquesPlanificacion = $planificacion->exists
            ? $planificacion->periodos()->get()->map(fn ($periodo): array => [
                ...$periodo->only(['orden', 'numero_inicio', 'numero_fin', 'duracion_minutos', 'dia_semana', 'hora_inicio', 'hora_fin']),
                'dia' => $periodo->dia_semana ? Horario::DIAS[$periodo->dia_semana] : 'Horario anterior',
                ...$periodo->only(['destreza_desempeno', 'indicadores_logro', 'recursos', 'tema', 'anticipacion', 'construccion', 'consolidacion', 'tecnicas_evaluacion']),
            ])
            : collect($asignacionSeleccionada ? $resumenHorarios[$asignacionSeleccionada->id]['bloques'] : []);

        return view('Planificaciones.form', compact('planificacion', 'asignaciones', 'resumenHorarios', 'asignacionSeleccionada', 'bloquesPlanificacion'));
    }

    public function show(Planificacion $planificacion): View
    {
        Gate::authorize('view', $planificacion);
        $planificacion->load(['asignacion.docente', 'asignacion.materia', 'asignacion.seccion.curso', 'asignacion.seccion.periodo', 'periodos', 'revisor', 'aprobador']);

        return view('Planificaciones.show', compact('planificacion'));
    }

    public function store(GuardarPlanificacionRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['version', 'periodos']);
        $contenidos = $request->validated('periodos');
        $planificacion = DB::transaction(function () use ($datos, $contenidos, $request): Planificacion {
            $asignacion = Asignacion::with('seccion.periodo')->findOrFail($datos['asignacion_id']);
            $this->validarContexto($asignacion, $datos, $request);
            $periodos = $this->construirPeriodos->handle($asignacion);
            $this->validarCantidadPeriodos($periodos->count(), $contenidos);
            $datos['numero_periodos'] = $periodos->sum(fn (array $periodo): int => $periodo['numero_fin'] - $periodo['numero_inicio'] + 1);
            $planificacion = Planificacion::create($datos);
            $planificacion->periodos()->createMany($periodos->values()->map(
                fn (array $periodo, int $indice): array => [...array_diff_key($periodo, ['dia' => true, 'incluye_recreo' => true]), ...$contenidos[$indice]]
            )->all());

            return $planificacion;
        }, 3);

        return redirect()->route('planificaciones.show', $planificacion)->with('success', 'Borrador guardado.');
    }

    public function update(GuardarPlanificacionRequest $request, Planificacion $planificacion): RedirectResponse
    {
        $datos = $request->safe()->except(['version', 'periodos']);
        $contenidos = $request->validated('periodos');
        DB::transaction(function () use ($datos, $contenidos, $request, $planificacion): void {
            $registro = Planificacion::lockForUpdate()->findOrFail($planificacion->id);
            Gate::authorize('update', $registro);
            $this->validarVersion($registro, $request->integer('version'));
            if ($registro->asignacion_id !== (int) $datos['asignacion_id']) {
                throw ValidationException::withMessages(['asignacion_id' => 'La asignación de una planificación ya creada no se puede cambiar.']);
            }
            $this->validarContexto($registro->asignacion->load('seccion.periodo'), $datos, $request);
            $registro->fill($datos);
            $registro->version++;
            $registro->save();
            $periodos = $registro->periodos()->lockForUpdate()->get();
            $this->validarCantidadPeriodos($periodos->count(), $contenidos);
            foreach ($periodos->values() as $indice => $periodo) {
                $periodo->update($contenidos[$indice]);
            }
        }, 3);

        return redirect()->route('planificaciones.show', $planificacion)->with('success', 'Borrador actualizado.');
    }

    public function submit(Request $request, Planificacion $planificacion): RedirectResponse
    {
        $datos = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($planificacion, $datos): void {
            $registro = Planificacion::lockForUpdate()->findOrFail($planificacion->id);
            $this->bloquearPeriodo($registro->asignacion);
            Gate::authorize('submit', $registro);
            $this->validarVersion($registro, (int) $datos['version']);
            if (blank($registro->objetivo_disciplinar)) {
                throw ValidationException::withMessages(['objetivo_disciplinar' => 'Completa este campo antes de enviar la planificación a revisión.']);
            }
            foreach ($registro->periodos()->get() as $indice => $periodo) {
                foreach (['destreza_desempeno', 'indicadores_logro', 'recursos', 'tema', 'anticipacion', 'construccion', 'consolidacion', 'tecnicas_evaluacion'] as $campo) {
                    if (blank($periodo->{$campo})) {
                        throw ValidationException::withMessages(["periodos.$indice.$campo" => 'Completa este campo antes de enviar la planificación a revisión.']);
                    }
                }
            }
            $registro->estado = 'en_revision';
            $registro->enviado_en = now();
            $registro->revisado_por = null;
            $registro->revisado_en = null;
            $registro->aprobado_por = null;
            $registro->aprobado_en = null;
            $registro->observaciones_revision = null;
            $registro->version++;
            $registro->save();
        }, 3);

        return redirect()->route('planificaciones.show', $planificacion)->with('success', 'Planificación enviada a revisión.');
    }

    public function review(Request $request, Planificacion $planificacion): RedirectResponse
    {
        return $this->decidir($request, $planificacion, 'review');
    }

    public function approve(Request $request, Planificacion $planificacion): RedirectResponse
    {
        return $this->decidir($request, $planificacion, 'approve');
    }

    public function devolver(Request $request, Planificacion $planificacion): RedirectResponse
    {
        return $this->decidir($request, $planificacion, 'devolver');
    }

    private function decidir(Request $request, Planificacion $planificacion, string $accion): RedirectResponse
    {
        Gate::authorize($accion, $planificacion);
        $datos = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'observaciones_revision' => [$accion === 'devolver' ? 'required' : 'nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($planificacion, $request, $accion, $datos): void {
            $registro = Planificacion::lockForUpdate()->findOrFail($planificacion->id);
            $this->bloquearPeriodo($registro->asignacion);
            Gate::authorize($accion, $registro);
            $this->validarVersion($registro, (int) $datos['version']);

            if ($accion === 'review') {
                $registro->estado = 'revisada';
                $registro->revisado_por = $request->user()->id;
                $registro->revisado_en = now();
            } elseif ($accion === 'approve') {
                $registro->estado = 'aprobada';
                $registro->aprobado_por = $request->user()->id;
                $registro->aprobado_en = now();
            } else {
                $registro->estado = 'devuelta';
            }

            if (filled($datos['observaciones_revision'] ?? null)) {
                $registro->observaciones_revision = $datos['observaciones_revision'];
            }
            $registro->version++;
            $registro->save();
        }, 3);

        $mensaje = match ($accion) {
            'review' => 'Planificación revisada.',
            'approve' => 'Planificación aprobada.',
            default => 'Planificación devuelta al docente.',
        };

        return redirect()->route('planificaciones.show', $planificacion)->with('success', $mensaje);
    }

    private function validarContexto(Asignacion $asignacion, array $datos, Request $request): void
    {
        abort_unless($request->user()->esDocente() && $asignacion->docente_id === $request->user()->docente_id, 403);
        $periodo = $this->bloquearPeriodo($asignacion);
        if ($periodo->cerrado) {
            throw ValidationException::withMessages(['asignacion_id' => 'No se pueden crear ni editar planificaciones de un período cerrado.']);
        }
        $inicio = Carbon::parse($datos['fecha_inicio']);
        $fin = Carbon::parse($datos['fecha_fin']);
        if ($inicio->lt($periodo->fecha_inicio) || $fin->gt($periodo->fecha_fin)) {
            throw ValidationException::withMessages(['fecha_inicio' => 'Las fechas deben estar dentro del período académico de la asignación.']);
        }
    }

    private function bloquearPeriodo(Asignacion $asignacion): PeriodoAcademico
    {
        return PeriodoAcademico::lockForUpdate()->findOrFail($asignacion->seccion->periodo_academico_id);
    }

    private function validarVersion(Planificacion $planificacion, int $version): void
    {
        if ($planificacion->version !== $version) {
            throw ValidationException::withMessages(['version' => 'La planificación cambió. Recarga la página antes de continuar.']);
        }
    }

    /** @param array<int, array<string, mixed>> $contenidos */
    private function validarCantidadPeriodos(int $cantidadEsperada, array $contenidos): void
    {
        if ($cantidadEsperada === 0) {
            throw ValidationException::withMessages(['asignacion_id' => 'Esta materia no tiene períodos configurados en el horario.']);
        }

        if ($cantidadEsperada !== count($contenidos)) {
            throw ValidationException::withMessages(['periodos' => 'Los bloques de la planificación no coinciden con el horario actual. Recarga el formulario.']);
        }
    }
}
