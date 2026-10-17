<x-layouts.aplicacion title="Mis materias">
    <div class="page-heading">
        <div>
            <h1>Mis materias</h1>
            <p>Selecciona una materia para consultar o crear sus planificaciones semanales.</p>
        </div>
    </div>
    <div class="teacher-subjects">
        @forelse($materias as $grupo)
            <section class="teacher-subject-group" aria-labelledby="materia-{{ $grupo['materia']->id }}">
                <div class="teacher-subject-heading">
                    <div><p class="eyebrow">MATERIA</p><h2 id="materia-{{ $grupo['materia']->id }}">{{ $grupo['materia']->nombre }}</h2></div>
                    <span class="course-badge">{{ $grupo['asignaciones']->count() }} {{ $grupo['asignaciones']->count() === 1 ? 'curso' : 'cursos' }}</span>
                </div>
                <div class="course-grid">
                    @foreach($grupo['asignaciones'] as $asignacion)
                        <article class="panel course-card">
                            <div class="course-card-labels"><span class="course-badge">Docente</span><span class="muted">{{ $asignacion->seccion->periodo->cerrado ? 'Período cerrado' : 'Período abierto' }}</span></div>
                            <h3>{{ $asignacion->seccion->curso->nombre }} <span>· {{ $asignacion->seccion->nombre }}</span></h3>
                            <p class="muted">{{ $asignacion->seccion->periodo->nombre }}</p>
                            <div class="course-card-footer">
                                <span>{{ $asignacion->planificaciones_count }} planificaciones</span>
                                <a class="primary-button" href="{{ route('planificaciones.index', ['asignacion_id' => $asignacion->id]) }}">Ver planificaciones</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="panel record-list"><p>No tienes materias asignadas. Contacta a Administración para revisar tus asignaciones.</p></section>
        @endforelse
    </div>
</x-layouts.aplicacion>
