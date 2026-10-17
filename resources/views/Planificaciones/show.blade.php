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
            <p class="eyebrow">PLAN MICROCURRICULAR SEMANAL</p>
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
                <h2>Plan microcurricular semanal</h2><span>Período lectivo
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
                    <dt>Área</dt>
                    <dd>{{ $planificacion->area ?: '—' }}</dd>
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
                    <dd>{{ $planificacion->trimestre }}</dd>
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
            <h3>Aprendizaje disciplinar</h3>
            <div class="planning-text-block">
                <h4>Objetivo disciplinar</h4>
                <p>{{ $planificacion->objetivo_disciplinar ?: '—' }}</p>
            </div>
            <div class="planning-text-block">
                <h4>Estándar de aprendizaje</h4>
                <p>{{ $planificacion->estandar_aprendizaje ?: '—' }}</p>
            </div>
        </section>
        <section class="planning-document-section">
            <h3>2. Planificación</h3>
            <div class="planning-period-documents">
                @foreach ($planificacion->periodos as $periodo)
                    <article class="planning-period-document">
                        <header>
                            <h4>{{ $periodo->numero_inicio === $periodo->numero_fin ? "Período {$periodo->numero_inicio}" : "Períodos {$periodo->numero_inicio} y {$periodo->numero_fin}" }}
                                <small>({{ $periodo->duracion_minutos }} min)</small></h4>
                            @if ($periodo->dia_semana)
                                <span>{{ App\Models\Horario::DIAS[$periodo->dia_semana] }} ·
                                    {{ substr($periodo->hora_inicio, 0, 5) }}–{{ substr($periodo->hora_fin, 0, 5) }}</span>
                            @endif
                        </header>
                        <div class="planning-content-grid">
                            @foreach ([
        'destreza_desempeno' => 'Destreza con criterio de desempeño',
        'indicadores_logro' => 'Indicadores de logro',
        'recursos' => 'Recursos',
        'tecnicas_evaluacion' => 'Técnicas / instrumentos de evaluación',
    ] as $campo => $etiqueta)
                                <div class="planning-text-block">
                                    <h4>{{ $etiqueta }}</h4>
                                    <p>{{ $periodo->{$campo} ?: '—' }}</p>
                                </div>
                            @endforeach
                            <section class="planning-methodology-block">
                                <h4>Estrategias metodológicas</h4>
                                <div class="planning-methodology-steps">
                                    @foreach (['tema' => 'Tema', 'anticipacion' => 'Anticipación', 'construccion' => 'Construcción', 'consolidacion' => 'Consolidación'] as $campo => $etiqueta)
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
            <div class="planning-text-block">
                <h4>Especifique la adaptación</h4>
                <p>{{ $planificacion->adaptacion_especificacion ?: '—' }}</p>
            </div>
            <div class="planning-content-grid">
                @foreach ([
        'adaptacion_destreza' => 'Destreza con criterio de desempeño',
        'adaptacion_indicadores' => 'Indicadores de logro',
        'adaptacion_recursos' => 'Recursos',
        'adaptacion_estrategias' => 'Estrategias metodológicas',
        'adaptacion_evaluacion' => 'Técnicas e instrumentos de evaluación',
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
                <p>Completa el objetivo y todos los campos de cada bloque antes de enviar.</p>
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
