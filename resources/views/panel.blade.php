<x-layouts.aplicacion>
    @php
        $esSuperadmin = auth()->user()->role === 'SUPERADMIN';
        $porcentajeAprobadas = $totalPlanificaciones > 0 ? (int) round($estados['aprobada'] * 100 / $totalPlanificaciones) : 0;
    @endphp
    <div class="page-heading">
        <div><p class="eyebrow">GESTIÓN ACADÉMICA</p><h1>Hola, {{ auth()->user()->name }}</h1><p>{{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }} · {{ $periodo ? 'Período '.$periodo->nombre : 'Sin período académico abierto' }}</p></div>
        <a class="primary-button" href="{{ route('planificaciones.index', ['estado' => 'en_revision']) }}">Revisar planificaciones</a>
    </div>

    <section class="stats dash-stats" aria-label="Estado de las planificaciones">
        @foreach([
            ['en_revision', 'Por revisar', 'Enviadas por docentes', 'bell', 'wine'],
            ['revisada', 'Por aprobar', 'Revisadas, pendientes de aprobación', 'check', 'blue'],
            ['devuelta', 'Devueltas', 'En corrección por el docente', 'exit', 'gold'],
            ['aprobada', 'Aprobadas', $porcentajeAprobadas.'% del período', 'check', 'green'],
        ] as [$estado, $etiqueta, $descripcion, $icono, $color])
            <a class="stat-card dash-stat-link" href="{{ route('planificaciones.index', array_filter(['estado' => $estado, 'periodo_id' => $periodo?->id])) }}">
                <div class="stat-top"><span>{{ $etiqueta }}</span><span class="icon-box {{ $color }}"><svg aria-hidden="true"><use href="#i-{{ $icono }}"/></svg></span></div>
                <strong>{{ $estados[$estado] }}</strong>
                <p>{{ $descripcion }}</p>
            </a>
        @endforeach
    </section>

    <div class="dash-grid">
        <section class="panel dash-queue" aria-labelledby="bandeja-titulo">
            <div class="panel-heading">
                <div><h2 id="bandeja-titulo">Bandeja de revisión</h2><p>Las más antiguas primero</p></div>
                @if($totalPendientes > $pendientes->count())
                    <a class="dash-link" href="{{ route('planificaciones.index', ['estado' => 'en_revision']) }}">Ver las {{ $totalPendientes }} <span aria-hidden="true">→</span></a>
                @endif
            </div>
            @forelse($pendientes as $planificacion)
                <a class="dash-queue-item" href="{{ route('planificaciones.show', $planificacion) }}">
                    <span class="dash-queue-week">S{{ $planificacion->numero_semana }}<small>T{{ $planificacion->trimestre }}</small></span>
                    <span class="dash-queue-copy">
                        <strong>{{ $planificacion->asignacion->materia->nombre }} · {{ $planificacion->asignacion->seccion->curso->nombre }} {{ $planificacion->asignacion->seccion->nombre }}</strong>
                        <small>{{ $planificacion->asignacion->docente->nombres }} {{ $planificacion->asignacion->docente->apellidos }} · {{ $planificacion->fecha_inicio->format('d/m') }} al {{ $planificacion->fecha_fin->format('d/m') }}@if($planificacion->enviado_en) · enviada {{ $planificacion->enviado_en->locale('es')->diffForHumans() }}@endif</small>
                    </span>
                    <span class="dash-status dash-status-{{ $planificacion->estado }}">{{ App\Models\Planificacion::ESTADOS[$planificacion->estado] }}</span>
                </a>
            @empty
                <div class="dash-empty"><svg aria-hidden="true"><use href="#i-check"/></svg><strong>Todo al día</strong><p>No hay planificaciones esperando revisión ni aprobación.</p></div>
            @endforelse
        </section>

        <div class="dash-side">
            <section class="panel" aria-labelledby="avance-titulo">
                <div class="panel-heading"><div><h2 id="avance-titulo">Avance del período</h2><p>{{ $totalPlanificaciones }} planificaciones registradas</p></div></div>
                <div class="dash-progress" role="img" aria-label="Distribución de planificaciones por estado">
                    @foreach(['aprobada', 'revisada', 'en_revision', 'devuelta', 'borrador'] as $estado)
                        @if($estados[$estado] > 0)<span class="dash-progress-{{ $estado }}" style="flex-grow: {{ $estados[$estado] }}"></span>@endif
                    @endforeach
                </div>
                <ul class="dash-legend">
                    @foreach(['aprobada', 'revisada', 'en_revision', 'devuelta', 'borrador'] as $estado)
                        <li><span class="dash-dot dash-progress-{{ $estado }}"></span>{{ App\Models\Planificacion::ESTADOS[$estado] }}<strong>{{ $estados[$estado] }}</strong></li>
                    @endforeach
                </ul>
            </section>

            <section class="panel" aria-labelledby="configuracion-titulo">
                <div class="panel-heading"><div><h2 id="configuracion-titulo">Preparación del período</h2><p>Lo que necesitan los docentes para planificar</p></div></div>
                <ul class="dash-checklist">
                    @foreach($configuracion as $elemento)
                        <li>
                            <a href="{{ $elemento['ruta'] }}" class="{{ $elemento['completo'] ? 'is-complete' : 'is-pending' }}">
                                <span class="dash-check-icon" aria-hidden="true">{{ $elemento['completo'] ? '✓' : '!' }}</span>
                                <span><strong>{{ $elemento['titulo'] }}</strong><small>{{ $elemento['detalle'] }}</small></span>
                                <span class="visually-hidden">{{ $elemento['completo'] ? 'Completo' : 'Pendiente' }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>

    <div class="section-heading"><h2>Accesos rápidos</h2><span>Planificación académica semanal</span></div>
    <nav class="dash-shortcuts" aria-label="Accesos rápidos">
        @foreach(array_filter([
            ['Planificaciones', 'Revisa y aprueba', 'planificaciones.index', 'book'],
            ['Seguimiento curricular', 'Destrezas utilizadas', 'seguimiento-curricular.index', 'check'],
            ['Cronograma lectivo', 'Trimestres y feriados', 'cronogramas.index', 'calendar'],
            ['Horarios', 'Períodos por curso', 'horarios.index', 'calendar'],
            ['Asignaciones', 'Docente, materia y curso', 'asignaciones.index', 'grid'],
            ['Catálogo curricular', 'Destrezas e indicadores', 'catalogo-curricular.index', 'book'],
            ['Docentes', 'Fichas docentes', 'docentes.index', 'users'],
            ['Materias', 'Asignaturas', 'materias.index', 'book'],
            ['Cursos y períodos', 'Paralelos y años', 'academico.catalogo', 'grid'],
            $esSuperadmin ? ['Usuarios', 'Cuentas y roles', 'usuarios.index', 'users'] : null,
        ]) as [$titulo, $descripcion, $ruta, $icono])
            <a class="dash-shortcut" href="{{ route($ruta) }}">
                <span class="icon-box green"><svg aria-hidden="true"><use href="#i-{{ $icono }}"/></svg></span>
                <span><strong>{{ $titulo }}</strong><small>{{ $descripcion }}</small></span>
            </a>
        @endforeach
    </nav>
</x-layouts.aplicacion>
