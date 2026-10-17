<x-layouts.aplicacion title="Panel principal">
    <div class="page-heading">
        <div><p class="eyebrow">MI ESPACIO DOCENTE</p><h1>Hola, {{ auth()->user()->name }}</h1><p>Organiza las planificaciones semanales de tus materias asignadas.</p></div>
        <a class="secondary-button" href="{{ route('portal-docente.index') }}">Ver mis materias</a>
    </div>
    <section class="stats teacher-stats" aria-label="Resumen académico">
        @foreach([['Cursos asignados', $secciones->count(), 'Cursos de períodos abiertos', 'grid', 'wine'], ['Materias', $materias->count(), 'Materias que impartes', 'book', 'green'], ['Planificaciones', $asignaciones->sum('planificaciones_count'), 'Registros creados', 'calendar', 'gold']] as [$label, $value, $description, $icon, $color])
            <article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="icon-box {{ $color }}"><svg><use href="#i-{{ $icon }}"/></svg></span></div><strong>{{ $value }}</strong><p>{{ $description }}</p></article>
        @endforeach
    </section>
    <div class="section-heading"><h2>Tus materias</h2><span>Períodos abiertos</span></div>
    <div class="course-grid">
        @forelse($asignaciones as $asignacion)
            <article class="panel course-card">
                <div class="course-card-labels"><span class="course-badge">{{ $asignacion->materia->nombre }}</span><span class="muted">{{ $asignacion->planificaciones_count }} planificaciones</span></div>
                <h2>{{ $asignacion->seccion->curso->nombre }} <span>· {{ $asignacion->seccion->nombre }}</span></h2>
                <p class="muted">{{ $asignacion->seccion->periodo->nombre }}</p>
                <div class="course-card-footer teacher-course-actions">
                    <a class="primary-button" href="{{ route('planificaciones.index', ['asignacion_id' => $asignacion->id]) }}">Ver planificaciones</a>
                </div>
            </article>
        @empty
            <section class="panel record-list"><h2>Aún no tienes materias en períodos abiertos</h2><p>Cuando Administración configure tus asignaciones, aparecerán aquí.</p></section>
        @endforelse
    </div>
</x-layouts.aplicacion>
