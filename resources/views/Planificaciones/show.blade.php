@php
    $asignacionPlanificacion = $planificacion->asignacion;
    $migasNavegacion = [
        ['label' => 'Planificaciones semanales', 'url' => route('planificaciones.index')],
        [
            'label' => $asignacionPlanificacion->materia->nombre,
            'url' => route('planificaciones.index', ['asignacion_id' => $asignacionPlanificacion->id]),
        ],
        ['label' => "Semana {$planificacion->numero_semana}", 'url' => null],
    ];
@endphp
<x-layouts.aplicacion title="Planificación semanal" :breadcrumbs="$migasNavegacion">
    @php
        $rolUsuario = auth()->user()->role;
        $esAdministracion = in_array($rolUsuario, ['SUPERADMIN', 'ADMIN'], true);
        $periodoAbierto = ! $planificacion->asignacion->seccion->periodo->cerrado;
        $esDocentePropietario = $rolUsuario === 'DOCENTE'
            && $planificacion->asignacion->docente_id === auth()->user()->docente_id;
        $puedeEditar = $esDocentePropietario
            && in_array($planificacion->estado, ['borrador', 'devuelta'], true)
            && $periodoAbierto;
        $puedeRevisar = $esAdministracion && $planificacion->estado === 'en_revision' && $periodoAbierto;
        $puedeAprobar = $esAdministracion && $planificacion->estado === 'revisada' && $periodoAbierto;
        $puedeDevolver = $esAdministracion
            && in_array($planificacion->estado, ['en_revision', 'revisada'], true)
            && $periodoAbierto;
        $nombreDocente = trim("{$planificacion->asignacion->docente->nombres} {$planificacion->asignacion->docente->apellidos}");
        $nombreTrimestre = ['Primer', 'Segundo', 'Tercer'][$planificacion->trimestre - 1].' trimestre';
    @endphp
    <div class="page-heading planning-toolbar">
        <div>
            <p class="eyebrow">PLANIFICACIÓN MICROCURRICULAR</p>
            <h1>Semana {{ $planificacion->numero_semana }} · {{ $planificacion->asignacion->materia->nombre }}</h1>
            <p>{{ $planificacion->asignacion->seccion->etiqueta }}</p>
        </div>
        <div class="planning-toolbar-actions"><a class="secondary-button"
                href="{{ route('planificaciones.index', $rolUsuario === 'DOCENTE' ? ['asignacion_id' => $planificacion->asignacion_id] : []) }}">Listado</a>
            @if($puedeEditar)
                <a class="secondary-button" href="{{ route('planificaciones.edit', $planificacion) }}">Editar</a>
            @endif
            <button class="primary-button" type="button" onclick="window.print()">
                Imprimir / Guardar PDF</button>
        </div>
    </div>
    @if (session('success'))
        <p class="success-message planning-notice" role="status">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <div class="delete-confirmation planning-notice" role="alert">
            <ul>
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($planificacion->observaciones_revision)
        <div class="planning-feedback planning-notice"><strong>Última observación</strong>
            <p>{{ $planificacion->observaciones_revision }}</p>
        </div>
    @endif

    <article class="panel planning-document planning-document-matrix">
        <header class="planning-matrix-header">
            <img src="{{ asset('images/logo-cwn.png') }}" alt="Emblema CWN" width="76" height="76">
            <div>
                <p>Escuela de Educación Básica</p>
                <h2>“CARMEN WITHER NAVARRO”</h2>
                <strong>PERÍODO LECTIVO {{ $planificacion->asignacion->seccion->periodo->nombre }}</strong>
            </div>
            <span class="planning-status planning-status-{{ $planificacion->estado }}">{{ App\Models\Planificacion::ESTADOS[$planificacion->estado] }}</span>
        </header>

        <div class="planning-matrix-title">Planificación microcurricular</div>

        <div class="planning-table-scroll">
            <table class="planning-info-matrix">
                <tbody>
                    <tr class="planning-matrix-section"><th colspan="12">1.- DATOS INFORMATIVOS:</th></tr>
                    <tr>
                        <th>Docente:</th><td colspan="4">{{ $nombreDocente }}</td>
                        <th>Asignatura:</th><td colspan="6">{{ $planificacion->asignacion->materia->nombre }}</td>
                    </tr>
                    <tr>
                        <th>Grado/Paralelo:</th>
                        <td colspan="2">{{ $planificacion->asignacion->seccion->curso->nombre }} “{{ $planificacion->asignacion->seccion->nombre }}”</td>
                        <th>Trimestre:</th><td colspan="2">{{ $nombreTrimestre }}</td>
                        <th>Título de la unidad:</th><td colspan="3">{{ $planificacion->titulo_unidad ?: '—' }}</td>
                        <th>Jornada:</th><td>{{ $planificacion->jornada }}</td>
                    </tr>
                    <tr>
                        <th>Semana:</th><td>{{ $planificacion->numero_semana }}</td>
                        <th>Desde:</th><td colspan="3">{{ $planificacion->fecha_inicio->format('d/m/Y') }}</td>
                        <th>Hasta:</th><td colspan="3">{{ $planificacion->fecha_fin->format('d/m/Y') }}</td>
                        <th>Períodos:</th><td>{{ $planificacion->numero_periodos }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="planning-table-scroll planning-objective-wrap">
            <table class="planning-objective-matrix">
                <colgroup>
                    <col class="objective-label">
                    <col class="objective-content">
                </colgroup>
                <thead>
                    <tr class="planning-matrix-section">
                        <th colspan="2">2.- Aprendizaje disciplinar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>Objetivos de aprendizaje:</th>
                        <td>{{ $planificacion->objetivos_aprendizaje ?: '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <section class="planning-curricular-matrices" aria-label="Desarrollo curricular por períodos">
            @foreach ($planificacion->periodos as $periodo)
                @php
                    $etiquetaPeriodo = $periodo->numero_inicio === $periodo->numero_fin
                        ? "Período {$periodo->numero_inicio}"
                        : "Períodos {$periodo->numero_inicio} y {$periodo->numero_fin}";
                @endphp
                <div class="planning-table-scroll planning-period-matrix-wrap">
                    <table class="planning-curricular-matrix">
                        <colgroup><col class="matrix-skill"><col class="matrix-indicator"><col class="matrix-resources"><col class="matrix-method"><col class="matrix-evaluation"></colgroup>
                        <thead>
                            <tr class="planning-period-band">
                                <th colspan="5">
                                    <span>{{ $etiquetaPeriodo }} · {{ $periodo->duracion_minutos }} min</span>
                                    @if ($periodo->dia_semana)
                                        <small>{{ App\Models\Horario::DIAS[$periodo->dia_semana] }}@if($periodo->fecha_clase) · {{ $periodo->fecha_clase->format('d/m/Y') }}@endif · {{ substr($periodo->hora_inicio, 0, 5) }}-{{ substr($periodo->hora_fin, 0, 5) }}</small>
                                    @endif
                                </th>
                            </tr>
                            <tr>
                                <th>Destrezas con criterios de desempeño</th>
                                <th>Indicadores de evaluación</th>
                                <th>Recursos</th>
                                <th>Orientaciones metodológicas para la enseñanza aprendizaje</th>
                                <th>Orientaciones para la evaluación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    @if($periodo->destrezaCurricular)
                                        <strong class="planning-curriculum-code">{{ $periodo->destrezaCurricular->codigo }}</strong>
                                        <small class="planning-curriculum-origin">{{ $periodo->destrezaCurricular->curriculo->nombre }}@if($periodo->destrezaCurricular->competencias->isNotEmpty()) · {{ $periodo->destrezaCurricular->competencias->pluck('codigo')->implode(', ') }}@endif</small>
                                    @endif
                                    <p>{{ $periodo->destrezas_criterios_desempeno ?: '—' }}</p>
                                </td>
                                <td>
                                    @if($periodo->indicadorEvaluacion)
                                        <strong class="planning-curriculum-code">{{ $periodo->indicadorEvaluacion->codigo }}</strong>
                                        <small class="planning-curriculum-origin">{{ $periodo->indicadorEvaluacion->curriculo->nombre }}@if($periodo->indicadorEvaluacion->competencias->isNotEmpty()) · {{ $periodo->indicadorEvaluacion->competencias->pluck('codigo')->implode(', ') }}@endif</small>
                                    @endif
                                    <p>{{ $periodo->indicadores_evaluacion ?: '—' }}</p>
                                </td>
                                <td><p>{{ $periodo->recursos ?: '—' }}</p></td>
                                <td>
                                    <div class="planning-matrix-topic"><strong>Tema:</strong><p>{{ $periodo->tema ?: '—' }}</p></div>
                                    @foreach (['experiencia' => 'Experiencia', 'reflexion' => 'Reflexión', 'conceptualizacion' => 'Conceptualización', 'aplicacion' => 'Aplicación'] as $campo => $etiqueta)
                                        @php($campoDua = "dua_{$campo}")
                                        <div class="planning-matrix-method">
                                            <strong>{{ $etiqueta }}</strong>
                                            <p>{{ $periodo->{$campo} ?: '—' }}</p>
                                            <div class="planning-matrix-dua"><b>DUA:</b> {{ $periodo->{$campoDua} ?: '—' }}</div>
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    <div class="planning-matrix-evaluation"><strong>Técnica</strong><p>{{ $periodo->tecnica_evaluacion ?: '—' }}</p></div>
                                    <div class="planning-matrix-evaluation"><strong>Instrumento</strong><p>{{ $periodo->instrumento_evaluacion ?: '—' }}</p></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
        </section>

        <div class="planning-table-scroll planning-adaptation-wrap">
            <table class="planning-adaptation-matrix">
                <thead><tr class="planning-matrix-section"><th colspan="5">3.- Adaptaciones curriculares</th></tr>
                    <tr><th>DESTREZAS CON CRITERIOS DE DESEMPEÑO</th><th>INDICADORES DE EVALUACIÓN</th><th>RECURSOS</th><th>ORIENTACIONES METODOLÓGICAS</th><th>ACTIVIDADES E INSTRUMENTOS DE EVALUACIÓN</th></tr>
                </thead>
                <tbody><tr>
                    <td>{{ $planificacion->adaptacion_destreza ?: '—' }}</td>
                    <td>{{ $planificacion->adaptacion_indicadores_evaluacion ?: '—' }}</td>
                    <td>{{ $planificacion->adaptacion_recursos ?: '—' }}</td>
                    <td>{{ $planificacion->adaptacion_orientaciones_metodologicas ?: '—' }}</td>
                    <td>{{ $planificacion->adaptacion_actividades_evaluacion_formativa ?: '—' }}</td>
                </tr></tbody>
            </table>
        </div>

        <table class="planning-signature-matrix">
            <thead><tr><th>ELABORADO POR</th><th>REVISADO POR</th><th>APROBADO POR</th></tr></thead>
            <tbody><tr>
                <td><strong>{{ $nombreDocente }}</strong><span>Docente · {{ $planificacion->created_at->format('d/m/Y') }}</span></td>
                <td><strong>{{ $planificacion->revisor?->name ?: 'Pendiente' }}</strong><span>{{ $planificacion->revisado_en?->format('d/m/Y H:i') ?: '—' }}</span></td>
                <td><strong>{{ $planificacion->aprobador?->name ?: 'Pendiente' }}</strong><span>{{ $planificacion->aprobado_en?->format('d/m/Y H:i') ?: '—' }}</span></td>
            </tr></tbody>
        </table>
    </article>

    <div class="planning-actions">
        @if($puedeEditar)
            <section class="panel">
                <h2>Enviar a revisión</h2>
                <p>Completa los objetivos de aprendizaje y todos los campos de cada bloque antes de enviar.</p>
                <form method="POST" action="{{ route('planificaciones.submit', $planificacion) }}">@csrf<input
                        type="hidden" name="version" value="{{ $planificacion->version }}"><button class="primary-button"
                        type="submit">Enviar a revisión</button></form>
            </section>
        @endif
        @if($puedeRevisar)
            <section class="panel">
                <h2>Revisión académica</h2>
                <p>Confirma la revisión o devuelve el plan con observaciones.</p>
                <form method="POST" action="{{ route('planificaciones.review', $planificacion) }}">@csrf<input
                        type="hidden" name="version" value="{{ $planificacion->version }}"><button class="primary-button"
                        type="submit">Marcar como revisada</button></form>
            </section>
        @endif
        @if($puedeAprobar)
            <section class="panel">
                <h2>Aprobación</h2>
                <p>Confirma la planificación revisada.</p>
                <form method="POST" action="{{ route('planificaciones.approve', $planificacion) }}">@csrf<input
                        type="hidden" name="version" value="{{ $planificacion->version }}"><button class="primary-button"
                        type="submit">Aprobar planificación</button></form>
            </section>
        @endif
        @if($puedeDevolver)
            <section class="panel">
                <h2>Devolver al docente</h2>
                <form method="POST" action="{{ route('planificaciones.devolver', $planificacion) }}">@csrf<input
                        type="hidden" name="version" value="{{ $planificacion->version }}">
                    <div class="field"><label for="observaciones_revision">Observaciones para corregir *</label>
                        <textarea id="observaciones_revision" name="observaciones_revision" rows="4" required>{{ old('observaciones_revision') }}</textarea>
                    </div><button class="secondary-button" type="submit">Devolver con observaciones</button>
                </form>
            </section>
        @endif
    </div>
</x-layouts.aplicacion>
