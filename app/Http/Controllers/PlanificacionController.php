<?php

namespace App\Http\Controllers;

use App\Actions\ConstruirPeriodosPlanificacion;
use App\Http\Requests\GuardarPlanificacionRequest;
use App\Models\Asignacion;
use App\Models\DestrezaCurricular;
use App\Models\Horario;
use App\Models\IndicadorEvaluacion;
use App\Models\ObjetivoCurricular;
use App\Models\PeriodoAcademico;
use App\Models\Planificacion;
use App\Models\UnidadLibro;
use App\Services\CalendarioPlanificaciones;
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
    public function __construct(private ConstruirPeriodosPlanificacion $construirPeriodos, private CalendarioPlanificaciones $calendario) {}

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

        $semanasPendientes = collect();
        $semanasPeriodo = collect();
        if ($asignacionSeleccionada && ! $asignacionSeleccionada->seccion->periodo->cerrado) {
            $registradas = Planificacion::where('asignacion_id', $asignacionSeleccionada->id)
                ->get(['id', 'fecha_inicio', 'estado'])
                ->keyBy(fn (Planificacion $planificacion): string => $planificacion->fecha_inicio->format('Y-m-d'));
            $semanasPeriodo = $this->calendario->semanas($asignacionSeleccionada)
                ->map(fn (array $semana): array => $semana + ['planificacion' => $registradas->get($semana['inicio'])]);
            $semanasPendientes = $semanasPeriodo->whereNull('planificacion')->values();
        }

        return view('Planificaciones.index', [
            'planificaciones' => $planificaciones,
            'periodos' => PeriodoAcademico::orderByDesc('fecha_inicio')->get(),
            'filtros' => $filtros,
            'asignaciones' => $asignaciones,
            'asignacionSeleccionada' => $asignacionSeleccionada,
            'semanasPendientes' => $semanasPendientes,
            'semanasPeriodo' => $semanasPeriodo,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Planificacion::class);
        $datos = $request->validate(['asignacion_id' => ['nullable', 'integer', 'exists:asignaciones,id'], 'fecha_inicio' => ['nullable', 'date_format:Y-m-d']]);

        return $this->formulario(new Planificacion, $request, isset($datos['asignacion_id']) ? (int) $datos['asignacion_id'] : null, $datos['fecha_inicio'] ?? null);
    }

    public function edit(Planificacion $planificacion, Request $request): View
    {
        Gate::authorize('update', $planificacion);

        return $this->formulario($planificacion, $request);
    }

    private function formulario(Planificacion $planificacion, Request $request, ?int $asignacionId = null, ?string $inicioSemana = null): View
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

        $semanaSeleccionada = null;
        if (! $planificacion->exists && $asignacionSeleccionada) {
            $semanas = $this->calendario->semanas($asignacionSeleccionada);
            if ($inicioSemana) {
                $semanaSeleccionada = $semanas->firstWhere('inicio', $inicioSemana);
                abort_unless($semanaSeleccionada, 404);
            } else {
                $fechasRegistradas = $asignacionSeleccionada->planificaciones()
                    ->pluck('fecha_inicio')
                    ->map(fn ($fecha) => Carbon::parse($fecha)->format('Y-m-d'));
                $semanaSeleccionada = $semanas
                    ->first(fn (array $semana): bool => ! $fechasRegistradas->contains($semana['inicio']));
            }
        }

        $bloquesPlanificacion = $planificacion->exists
            ? $planificacion->periodos()->get()->map(fn ($periodo): array => [
                ...$periodo->only(['orden', 'numero_inicio', 'numero_fin', 'duracion_minutos', 'dia_semana', 'fecha_clase', 'hora_inicio', 'hora_fin']),
                'dia' => $periodo->dia_semana ? Horario::DIAS[$periodo->dia_semana] : 'Horario anterior',
                ...$periodo->only([
                    'tema_libro_id',
                    'destreza_curricular_id', 'indicador_evaluacion_id',
                    'destrezas_criterios_desempeno', 'indicadores_evaluacion', 'recursos',
                    'tema', 'experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion',
                    'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion',
                    'orientaciones_evaluacion', 'tecnica_evaluacion', 'instrumento_evaluacion',
                ]),
            ])
            : ($semanaSeleccionada['bloques'] ?? collect($asignacionSeleccionada ? $resumenHorarios[$asignacionSeleccionada->id]['bloques'] : []));

        $grado = $asignacionSeleccionada?->seccion?->curso?->grado;
        $objetivosCurriculares = collect();
        $objetivosSeleccionados = collect(old(
            'objetivo_curricular_ids',
            $planificacion->exists ? $planificacion->objetivosCurriculares()->pluck('objetivos_curriculares.id')->all() : [],
        ))->filter()->map(fn ($id): int => (int) $id)->values();
        $destrezasCurriculares = collect();
        $indicadoresPorDestreza = [];
        $unidadesLibro = collect();
        $catalogoUnidades = [];
        if ($asignacionSeleccionada) {
            $objetivosCurriculares = ObjetivoCurricular::where('materia_id', $asignacionSeleccionada->materia_id)
                ->where('subnivel', $asignacionSeleccionada->seccion->curso->subnivel)
                ->where('activo', true)->orderBy('codigo')->get();
        }
        if ($asignacionSeleccionada && $grado) {
            $destrezasCurriculares = DestrezaCurricular::with(['curriculo', 'competencias', 'indicadores' => fn ($consulta) => $consulta
                ->where('indicadores_evaluacion.activo', true)->orderBy('curriculo_id')->orderBy('codigo'), 'indicadores.curriculo', 'indicadores.competencias'])
                ->where('materia_id', $asignacionSeleccionada->materia_id)
                ->where('grado', $grado)->where('activo', true)
                ->orderBy('curriculo_id')->orderBy('grupo')->orderBy('codigo')->get();
            $indicadoresPorDestreza = $destrezasCurriculares->mapWithKeys(fn (DestrezaCurricular $destreza): array => [
                $destreza->id => $destreza->indicadores->map(fn (IndicadorEvaluacion $indicador): array => [
                    'id' => $indicador->id,
                    'codigo' => $indicador->codigo,
                    'descripcion' => $indicador->descripcion,
                    'curriculo' => $indicador->curriculo->nombre,
                    'competencias' => $indicador->competencias->pluck('codigo')->values()->all(),
                ])->values()->all(),
            ])->all();

            $unidadesLibro = UnidadLibro::query()
                ->with([
                    'libroTexto:id,titulo',
                    'objetivos' => fn ($consulta) => $consulta->where('activo', true)->orderBy('codigo'),
                    'temas' => fn ($consulta) => $consulta->where('activo', true)->orderBy('orden'),
                ])
                ->where('activo', true)
                ->whereHas('libroTexto', fn ($consulta) => $consulta
                    ->where('materia_id', $asignacionSeleccionada->materia_id)
                    ->where('grado', $grado)
                    ->where('activo', true))
                ->orderBy('libro_texto_id')
                ->orderBy('numero')
                ->get();
            $catalogoUnidades = $unidadesLibro->mapWithKeys(fn (UnidadLibro $unidad): array => [
                $unidad->id => [
                    'id' => $unidad->id,
                    'numero' => $unidad->numero,
                    'titulo' => $unidad->titulo,
                    'objetivos' => $unidad->objetivos->map->only(['id', 'codigo', 'descripcion'])->values()->all(),
                    'temas' => $unidad->temas->map(fn ($tema): array => [
                        'id' => $tema->id,
                        'etiqueta' => $tema->etiqueta,
                        'titulo' => $tema->titulo,
                        'destreza_curricular_id' => $tema->destreza_curricular_id,
                        'indicador_evaluacion_id' => $tema->indicador_evaluacion_id,
                        'disponible' => (bool) ($tema->destreza_curricular_id && $tema->indicador_evaluacion_id),
                    ])->values()->all(),
                ],
            ])->all();
        }

        return view('Planificaciones.form', compact('planificacion', 'asignaciones', 'resumenHorarios', 'asignacionSeleccionada', 'bloquesPlanificacion', 'grado', 'objetivosCurriculares', 'objetivosSeleccionados', 'destrezasCurriculares', 'indicadoresPorDestreza', 'unidadesLibro', 'catalogoUnidades', 'semanaSeleccionada'));
    }

    public function show(Planificacion $planificacion): View
    {
        Gate::authorize('view', $planificacion);
        $planificacion->load(['asignacion.docente', 'asignacion.materia', 'asignacion.seccion.curso', 'asignacion.seccion.periodo', 'periodos.destrezaCurricular.curriculo', 'periodos.destrezaCurricular.competencias', 'periodos.indicadorEvaluacion.curriculo', 'periodos.indicadorEvaluacion.competencias', 'revisor', 'aprobador']);

        return view('Planificaciones.show', compact('planificacion'));
    }

    public function store(GuardarPlanificacionRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['version', 'periodos', 'objetivo_curricular_ids']);
        $contenidos = $request->validated('periodos');
        $objetivoIds = $request->validated('objetivo_curricular_ids', []);
        $planificacion = DB::transaction(function () use ($datos, $contenidos, $objetivoIds, $request): Planificacion {
            $asignacion = Asignacion::with(['seccion.periodo', 'seccion.curso'])->findOrFail($datos['asignacion_id']);
            $this->validarContexto($asignacion, $datos, $request);
            [$unidadLibro, $objetivoIds, $objetivosTexto, $tituloUnidad] = $this->prepararUnidadLibro(
                $asignacion,
                isset($datos['unidad_libro_id']) ? (int) $datos['unidad_libro_id'] : null,
                $objetivoIds,
                $datos['titulo_unidad'] ?? null,
            );
            $contenidos = $this->prepararSeleccionesCurriculares($asignacion->load('seccion.curso'), $contenidos, $unidadLibro);
            $periodos = $this->construirPeriodos->handle($asignacion, $datos['fecha_inicio']);
            $this->validarCantidadPeriodos($periodos->count(), $contenidos);
            $datos['numero_periodos'] = $periodos->sum(fn (array $periodo): int => $periodo['numero_fin'] - $periodo['numero_inicio'] + 1);
            $datos['objetivos_aprendizaje'] = $objetivosTexto;
            $datos['unidad_libro_id'] = $unidadLibro?->id;
            $datos['titulo_unidad'] = $tituloUnidad;
            $datos['jornada'] = Planificacion::JORNADA_INSTITUCIONAL;
            $planificacion = Planificacion::create($datos);
            $planificacion->objetivosCurriculares()->sync($objetivoIds);
            $planificacion->periodos()->createMany($periodos->values()->map(
                fn (array $periodo, int $indice): array => [...array_diff_key($periodo, ['dia' => true, 'incluye_recreo' => true]), ...$contenidos[$indice]]
            )->all());

            return $planificacion;
        }, 3);

        return redirect()->route('planificaciones.show', $planificacion)->with('success', 'Borrador guardado.');
    }

    public function update(GuardarPlanificacionRequest $request, Planificacion $planificacion): RedirectResponse
    {
        $datos = $request->safe()->except(['version', 'periodos', 'objetivo_curricular_ids']);
        $contenidos = $request->validated('periodos');
        $objetivoIds = $request->validated('objetivo_curricular_ids', []);
        DB::transaction(function () use ($datos, $contenidos, $objetivoIds, $request, $planificacion): void {
            $registro = Planificacion::lockForUpdate()->findOrFail($planificacion->id);
            Gate::authorize('update', $registro);
            $this->validarVersion($registro, $request->integer('version'));
            if ($registro->asignacion_id !== (int) $datos['asignacion_id']) {
                throw ValidationException::withMessages(['asignacion_id' => 'La asignación de una planificación ya creada no se puede cambiar.']);
            }
            $this->validarContexto($registro->asignacion->load('seccion.periodo'), $datos, $request);
            [$unidadLibro, $objetivoIds, $objetivosTexto, $tituloUnidad] = $this->prepararUnidadLibro(
                $registro->asignacion->load('seccion.curso'),
                isset($datos['unidad_libro_id']) ? (int) $datos['unidad_libro_id'] : null,
                $objetivoIds,
                $datos['titulo_unidad'] ?? null,
            );
            $contenidos = $this->prepararSeleccionesCurriculares($registro->asignacion->load('seccion.curso'), $contenidos, $unidadLibro);
            $datos['objetivos_aprendizaje'] = $objetivosTexto;
            $datos['unidad_libro_id'] = $unidadLibro?->id;
            $datos['titulo_unidad'] = $tituloUnidad;
            $datos['jornada'] = Planificacion::JORNADA_INSTITUCIONAL;
            $registro->fill($datos);
            $registro->version++;
            $registro->save();
            $registro->objetivosCurriculares()->sync($objetivoIds);
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
            if (blank($registro->objetivos_aprendizaje)) {
                throw ValidationException::withMessages(['objetivos_aprendizaje' => 'Completa este campo antes de enviar la planificación a revisión.']);
            }
            foreach ($registro->periodos()->get() as $indice => $periodo) {
                foreach ([
                    'destreza_curricular_id', 'indicador_evaluacion_id', 'destrezas_criterios_desempeno', 'indicadores_evaluacion', 'recursos',
                    'tema', 'experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion',
                    'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion',
                    'tecnica_evaluacion', 'instrumento_evaluacion',
                ] as $campo) {
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

    /** @param array<int, array<string, mixed>> $contenidos
     * @return array<int, array<string, mixed>>
     */
    private function prepararSeleccionesCurriculares(Asignacion $asignacion, array $contenidos, ?UnidadLibro $unidadLibro = null): array
    {
        $grado = $asignacion->seccion->curso->grado;
        if (! $grado) {
            throw ValidationException::withMessages(['asignacion_id' => 'El curso no tiene configurado su número de grado.']);
        }

        foreach ($contenidos as $indice => &$contenido) {
            $temaLibroId = isset($contenido['tema_libro_id']) ? (int) $contenido['tema_libro_id'] : 0;
            if ($temaLibroId) {
                $temaLibro = $unidadLibro?->temas->firstWhere('id', $temaLibroId);
                if (! $temaLibro || ! $temaLibro->activo) {
                    throw ValidationException::withMessages([
                        "periodos.$indice.tema_libro_id" => 'Selecciona un tema que pertenezca a la unidad elegida.',
                    ]);
                }
                if (! $temaLibro->destreza_curricular_id || ! $temaLibro->indicador_evaluacion_id) {
                    throw ValidationException::withMessages([
                        "periodos.$indice.tema_libro_id" => 'Este tema todavía no tiene una destreza y un indicador disponibles en el catálogo.',
                    ]);
                }

                $contenido['tema_libro_id'] = $temaLibro->id;
                $contenido['tema'] = $temaLibro->titulo;
                $contenido['destreza_curricular_id'] = $temaLibro->destreza_curricular_id;
                $contenido['indicador_evaluacion_id'] = $temaLibro->indicador_evaluacion_id;
            } else {
                $contenido['tema_libro_id'] = null;
            }

            $destrezaId = isset($contenido['destreza_curricular_id']) ? (int) $contenido['destreza_curricular_id'] : 0;
            $indicadorId = isset($contenido['indicador_evaluacion_id']) ? (int) $contenido['indicador_evaluacion_id'] : 0;
            if (! $destrezaId && ! $indicadorId) {
                $contenido['destrezas_criterios_desempeno'] = null;
                $contenido['indicadores_evaluacion'] = null;

                continue;
            }
            if (! $destrezaId || ! $indicadorId) {
                throw ValidationException::withMessages(["periodos.$indice.indicador_evaluacion_id" => 'Selecciona una destreza y uno de sus indicadores relacionados.']);
            }

            $destreza = DestrezaCurricular::whereKey($destrezaId)->where('materia_id', $asignacion->materia_id)
                ->where('grado', $grado)->where('activo', true)->first();
            $indicador = IndicadorEvaluacion::whereKey($indicadorId)->where('materia_id', $asignacion->materia_id)
                ->where('grado', $grado)->where('activo', true)->first();
            if (! $destreza || ! $indicador || ! $destreza->indicadores()->whereKey($indicador->id)->exists()) {
                throw ValidationException::withMessages(["periodos.$indice.indicador_evaluacion_id" => 'El indicador seleccionado no corresponde a la destreza, materia y grado de esta planificación.']);
            }
            $contenido['destrezas_criterios_desempeno'] = $destreza->descripcion;
            $contenido['indicadores_evaluacion'] = $indicador->descripcion;
        }
        unset($contenido);

        return $contenidos;
    }

    /** @return array{0: ?UnidadLibro, 1: array<int>, 2: ?string, 3: ?string} */
    private function prepararUnidadLibro(
        Asignacion $asignacion,
        ?int $unidadLibroId,
        array $objetivoIds,
        ?string $tituloUnidad,
    ): array {
        $grado = $asignacion->seccion->curso->grado;
        $consultaUnidades = UnidadLibro::query()
            ->where('activo', true)
            ->whereHas('libroTexto', fn ($consulta) => $consulta
                ->where('materia_id', $asignacion->materia_id)
                ->where('grado', $grado)
                ->where('activo', true));

        if (! $unidadLibroId) {
            if ($consultaUnidades->exists()) {
                throw ValidationException::withMessages([
                    'unidad_libro_id' => 'Selecciona la unidad del libro que se trabajará esta semana.',
                ]);
            }

            [$objetivoIds, $objetivosTexto] = $this->prepararObjetivosCurriculares($asignacion, $objetivoIds);

            return [null, $objetivoIds, $objetivosTexto, $tituloUnidad];
        }

        $unidadLibro = $consultaUnidades
            ->with([
                'objetivos' => fn ($consulta) => $consulta->where('activo', true)->orderBy('codigo'),
                'temas' => fn ($consulta) => $consulta->where('activo', true)->orderBy('orden'),
            ])
            ->find($unidadLibroId);
        if (! $unidadLibro) {
            throw ValidationException::withMessages([
                'unidad_libro_id' => 'La unidad seleccionada no corresponde a la materia y grado de esta planificación.',
            ]);
        }

        $objetivos = $unidadLibro->objetivos;
        $texto = $objetivos
            ->map(fn (ObjetivoCurricular $objetivo): string => $objetivo->codigo.' '.$objetivo->descripcion)
            ->implode("\n\n");

        return [$unidadLibro, $objetivos->modelKeys(), $texto ?: null, $unidadLibro->titulo];
    }

    /** @return array{0: array<int>, 1: ?string} */
    private function prepararObjetivosCurriculares(Asignacion $asignacion, array $objetivoIds): array
    {
        $ids = collect($objetivoIds)->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [[], null];
        }

        $objetivos = ObjetivoCurricular::whereIn('id', $ids)
            ->where('materia_id', $asignacion->materia_id)
            ->where('subnivel', $asignacion->seccion->curso->subnivel)
            ->where('activo', true)->get()->keyBy('id');

        if ($objetivos->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'objetivo_curricular_ids' => 'Selecciona objetivos que correspondan a la materia y al subnivel del curso.',
            ]);
        }

        $texto = $ids->map(fn (int $id): string => $objetivos[$id]->codigo.' '.$objetivos[$id]->descripcion)->implode("\n\n");

        return [$ids->all(), $texto];
    }
}
