<x-layouts.aplicacion title="Panel principal">
    @php
        $viernes = $lunes->copy()->addDays(4);
        $semanaLista = $semana->filter(fn (array $fila): bool => $fila['planificacion'] !== null)->count();
    @endphp
    <div class="page-heading">
        <div><p class="eyebrow">MI ESPACIO DOCENTE</p><h1>Hola, {{ auth()->user()->name }}</h1><p>Organiza las planificaciones semanales de tus materias asignadas.</p></div>
        <a class="secondary-button" href="{{ route('portal-docente.index') }}">Ver mis materias</a>
    </div>

    @if($devueltas->isNotEmpty())
        <section class="dash-alert" aria-labelledby="devueltas-titulo">
            <div class="dash-alert-heading">
                <span class="dash-check-icon" aria-hidden="true">!</span>
                <div><h2 id="devueltas-titulo">{{ $devueltas->count() === 1 ? 'Tienes 1 planificación devuelta' : "Tienes {$devueltas->count()} planificaciones devueltas" }}</h2><p>Revisa las observaciones, corrígelas y envíalas nuevamente.</p></div>
            </div>
            <ul>
                @foreach($devueltas as $planificacion)
                    <li>
                        <a href="{{ route('planificaciones.show', $planificacion) }}">
                            <strong>{{ $planificacion->asignacion->materia->nombre }} · {{ $planificacion->asignacion->seccion->curso->nombre }} {{ $planificacion->asignacion->seccion->nombre }} · Semana {{ $planificacion->numero_semana }}</strong>
                            @if($planificacion->observaciones_revision)<small>{{ \Illuminate\Support\Str::limit($planificacion->observaciones_revision, 140) }}</small>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="stats dash-stats" aria-label="Resumen académico">
        @foreach([
            ['Cursos asignados', $secciones->count(), 'Cursos de períodos abiertos', 'grid', 'wine'],
            ['Materias', $materias->count(), 'Materias que impartes', 'book', 'green'],
            ['Planificaciones', $asignaciones->sum('planificaciones_count'), $asignaciones->sum('aprobadas_count').' aprobadas', 'calendar', 'gold'],
            ['En revisión', $enRevision, 'Esperando a Administración', 'bell', 'blue'],
        ] as [$label, $value, $description, $icon, $color])
            <article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="icon-box {{ $color }}"><svg aria-hidden="true"><use href="#i-{{ $icon }}"/></svg></span></div><strong>{{ $value }}</strong><p>{{ $description }}</p></article>
        @endforeach
    </section>

    @if($semana->isNotEmpty())
        <section class="panel dash-week" aria-labelledby="semana-titulo">
            <div class="panel-heading">
                <div>
                    <h2 id="semana-titulo">{{ $lunes->isSameWeek(now()) ? 'Tu semana' : 'Próxima semana' }}</h2>
                    <p>Del {{ $lunes->locale('es')->isoFormat('D [de] MMMM') }} al {{ $viernes->locale('es')->isoFormat('D [de] MMMM') }}</p>
                </div>
                <span class="soft-badge">{{ $semanaLista }} de {{ $semana->count() }} planificadas</span>
            </div>
            <div class="dash-progress dash-progress-single" role="progressbar" aria-label="Materias planificadas esta semana" aria-valuemin="0" aria-valuemax="{{ $semana->count() }}" aria-valuenow="{{ $semanaLista }}">
                <span class="dash-progress-aprobada" style="width: {{ $semana->count() ? round($semanaLista * 100 / $semana->count()) : 0 }}%"></span>
            </div>
            <ul class="dash-week-list">
                @foreach($semana as $fila)
                    @php($asignacion = $fila['asignacion'])
                    <li>
                        <span class="dash-week-copy">
                            <strong>{{ $asignacion->materia->nombre }}</strong>
                            <small>{{ $asignacion->seccion->curso->nombre }} {{ $asignacion->seccion->nombre }} · {{ $fila['periodos'] }} {{ $fila['periodos'] === 1 ? 'período' : 'períodos' }}</small>
                        </span>
                        @if($fila['planificacion'])
                            <span class="dash-status dash-status-{{ $fila['planificacion']->estado }}">{{ App\Models\Planificacion::ESTADOS[$fila['planificacion']->estado] }}</span>
                            <a class="secondary-button" href="{{ route('planificaciones.show', $fila['planificacion']) }}">Abrir</a>
                        @else
                            <span class="dash-status dash-status-pendiente">Sin planificar</span>
                            <a class="primary-button" href="{{ route('planificaciones.create', ['asignacion_id' => $asignacion->id, 'fecha_inicio' => $fila['inicio']]) }}">Planificar</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="section-heading"><h2>Tus materias</h2><span>Períodos abiertos</span></div>
    <div class="course-grid">
        @forelse($asignaciones as $asignacion)
            <article class="panel course-card">
                <div class="course-card-labels"><span class="course-badge">{{ $asignacion->materia->nombre }}</span><span class="muted">{{ $asignacion->planificaciones_count }} planificaciones</span></div>
                <h2>{{ $asignacion->seccion->curso->nombre }} <span>· {{ $asignacion->seccion->nombre }}</span></h2>
                <p class="muted">{{ $asignacion->seccion->periodo->nombre }} · {{ $asignacion->aprobadas_count }} aprobadas</p>
                <div class="course-card-footer teacher-course-actions">
                    <a class="primary-button" href="{{ route('planificaciones.index', ['asignacion_id' => $asignacion->id]) }}">Ver planificaciones</a>
                </div>
            </article>
        @empty
            <section class="panel record-list"><h2>Aún no tienes materias en períodos abiertos</h2><p>Cuando Administración configure tus asignaciones, aparecerán aquí.</p></section>
        @endforelse
    </div>
</x-layouts.aplicacion>
