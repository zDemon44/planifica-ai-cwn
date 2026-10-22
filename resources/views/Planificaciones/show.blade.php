<x-layouts.aplicacion title="Planificación semanal">
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

    <article class="panel planning-document">
        <header class="planning-document-header"><img src="{{ asset('images/logo-cwn.png') }}" alt="Emblema CWN"
                width="82" height="82">
            <div>
                <p>Escuela de Educación Básica Carmen Wither Navarro</p>
                <h2>Planificación microcurricular</h2><span>Período lectivo
                    {{ $planificacion->asignacion->seccion->periodo->nombre }}</span>
            </div><span
                class="planning-status planning-status-{{ $planificacion->estado }}">{{ App\Models\Planificacion::ESTADOS[$planificacion->estado] }}</span>
        </header>
        <section class="planning-document-section">
            <h3>1. Datos informativos</h3>
            <dl class="planning-details">
                <div>
                    <dt>Docente</dt>
                    <dd>{{ $planificacion->asignacion->docente->nombres }}
                        {{ $planificacion->asignacion->docente->apellidos }}</dd>
                </div>
                <div>
                    <dt>Asignatura</dt>
                    <dd>{{ $planificacion->asignacion->materia->nombre }}</dd>
                </div>
                <div>
                    <dt>Número de períodos</dt>
                    <dd>{{ $planificacion->numero_periodos }}</dd>
                </div>
                <div>
                    <dt>Grado / curso</dt>
                    <dd>{{ $planificacion->asignacion->seccion->curso->nombre }}</dd>
                </div>
                <div>
                    <dt>Paralelo</dt>
                    <dd>{{ $planificacion->asignacion->seccion->nombre }}</dd>
                </div>
                <div>
                    <dt>Trimestre</dt>
                    <dd>{{ ['Primer', 'Segundo', 'Tercer'][$planificacion->trimestre - 1] }} trimestre</dd>
                </div>
                <div>
                    <dt>Título de la unidad</dt>
                    <dd>{{ $planificacion->titulo_unidad ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Jornada</dt>
                    <dd>{{ ucfirst($planificacion->jornada) }}</dd>
                </div>
                <div>
                    <dt>Semana</dt>
                    <dd>{{ $planificacion->numero_semana }}</dd>
                </div>
                <div>
                    <dt>Fecha de inicio</dt>
                    <dd>{{ $planificacion->fecha_inicio->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt>Fecha de finalización</dt>
                    <dd>{{ $planificacion->fecha_fin->format('d/m/Y') }}</dd>
                </div>
            </dl>
        </section>
        <section class="planning-document-section">
            <h3>2. Aprendizaje disciplinar</h3>
            <div class="planning-text-block">
                <h4>Objetivos de aprendizaje</h4>
                <p>{{ $planificacion->objetivos_aprendizaje ?: '—' }}</p>
            </div>
        </section>
        <section class="planning-document-section">
            <h3>Desarrollo por bloques de períodos</h3>
            <div class="planning-period-documents">
                @foreach ($planificacion->periodos as $periodo)
                    <article class="planning-period-document">
                        <header>
                            <h4>{{ $periodo->numero_inicio === $periodo->numero_fin ? "Período {$periodo->numero_inicio}" : "Períodos {$periodo->numero_inicio} y {$periodo->numero_fin}" }}
                                <small>({{ $periodo->duracion_minutos }} min)</small></h4>
                            @if ($periodo->dia_semana)
                                <span>{{ App\Models\Horario::DIAS[$periodo->dia_semana] }} ·
                                    @if($periodo->fecha_clase){{ $periodo->fecha_clase->format('d/m/Y') }} · @endif
                                    {{ substr($periodo->hora_inicio, 0, 5) }}–{{ substr($periodo->hora_fin, 0, 5) }}</span>
                            @endif
                        </header>
                        <div class="planning-content-grid">
                            @foreach ([
        'destrezas_criterios_desempeno' => 'Destrezas con criterios de desempeño (estándar)',
        'indicadores_evaluacion' => 'Indicadores de evaluación',
        'recursos' => 'Recursos',
        'orientaciones_evaluacion' => 'Orientaciones para la evaluación',
    ] as $campo => $etiqueta)
                                <div class="planning-text-block">
                                    <h4>{{ $etiqueta }}
                                        @if($campo === 'destrezas_criterios_desempeno' && $periodo->destrezaCurricular)
                                            · {{ $periodo->destrezaCurricular->codigo }}@if($periodo->destrezaCurricular->competencias->isNotEmpty()) · {{ $periodo->destrezaCurricular->competencias->pluck('codigo')->implode(', ') }}@endif
                                        @elseif($campo === 'indicadores_evaluacion' && $periodo->indicadorEvaluacion)
                                            · {{ $periodo->indicadorEvaluacion->codigo }}@if($periodo->indicadorEvaluacion->competencias->isNotEmpty()) · {{ $periodo->indicadorEvaluacion->competencias->pluck('codigo')->implode(', ') }}@endif
                                        @endif
                                    </h4>
                                    <p>{{ $periodo->{$campo} ?: '—' }}</p>
                                </div>
                            @endforeach
                            <section class="planning-methodology-block">
                                <h4>Orientaciones metodológicas para la enseñanza aprendizaje</h4>
                                <div class="planning-methodology-steps">
                                    @foreach (['tema' => 'Tema', 'experiencia' => 'Experiencia', 'reflexion' => 'Reflexión', 'conceptualizacion' => 'Conceptualización', 'aplicacion' => 'Aplicación'] as $campo => $etiqueta)
                                        <div><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            <div>
                                                <h5>{{ $etiqueta }}</h5>
                                                <p>{{ $periodo->{$campo} ?: '—' }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        <section class="planning-document-section">
            <h3>3. Adaptaciones curriculares</h3>
            <div class="planning-content-grid">
                @foreach ([
        'adaptacion_destreza' => 'Destrezas con criterios de desempeño',
        'adaptacion_indicadores_evaluacion' => 'Indicadores de evaluación',
        'adaptacion_recursos' => 'Recursos',
        'adaptacion_orientaciones_metodologicas' => 'Orientaciones metodológicas para la enseñanza aprendizaje',
        'adaptacion_actividades_evaluacion_formativa' => 'Actividades e instrumentos de evaluación formativa',
    ] as $campo => $etiqueta)
                    <div class="planning-text-block">
                        <h4>{{ $etiqueta }}</h4>
                        <p>{{ $planificacion->{$campo} ?: '—' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="planning-document-section">
            <h3>Registro de elaboración, revisión y aprobación</h3>
            <div class="planning-signatures">
                <div><strong>Elaborado</strong>
                    <p>{{ $planificacion->asignacion->docente->nombres }}
                        {{ $planificacion->asignacion->docente->apellidos }}</p><small>Docente ·
                        {{ $planificacion->created_at->format('d/m/Y') }}</small>
                </div>
                <div><strong>Revisado</strong>
                    <p>{{ $planificacion->revisor?->name ?: 'Pendiente' }}</p>
                    <small>{{ $planificacion->revisado_en?->format('d/m/Y H:i') ?: '—' }}</small>
                </div>
                <div><strong>Aprobado</strong>
                    <p>{{ $planificacion->aprobador?->name ?: 'Pendiente' }}</p>
                    <small>{{ $planificacion->aprobado_en?->format('d/m/Y H:i') ?: '—' }}</small>
                </div>
            </div>
        </section>
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
