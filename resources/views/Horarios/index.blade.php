<x-layouts.aplicacion title="Horarios">
    <div class="page-heading">
        <div>
            <p class="eyebrow">ORGANIZACIÓN SEMANAL</p>
            <h1>{{ auth()->user()->role === 'DOCENTE' ? 'Mi horario' : 'Horarios de clases' }}</h1>
            <p>Un docente responsable por curso, con sus materias y períodos semanales.</p>
        </div>
        @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
            <div class="planning-toolbar-actions"><a class="secondary-button" href="{{ route('recreos.index') }}">Configurar
                    recreos</a><a class="primary-button" href="{{ route('horarios.create') }}">+ Crear horario</a></div>
        @endif
    </div>
    @if (session('success'))
        <p class="success-message" role="status">{{ session('success') }}</p>
    @endif
    <div class="schedule-list">
        @forelse($horarios as $horario)
            <article class="panel schedule-card">
                <div class="schedule-card-heading">
                    <div><span class="soft-badge">{{ $horario->periodo->nombre }}</span>
                        <h2>{{ $horario->seccion->curso->nombre }} · {{ $horario->seccion->nombre }}</h2>
                        <p>{{ $horario->docente->nombres }} {{ $horario->docente->apellidos }}</p>
                    </div>
                    <div class="schedule-stat"><strong>{{ $horario->bloques->count() }}</strong><span>períodos</span>
                    </div>
                </div>
                <div class="schedule-subjects">
                    @foreach ($horario->bloques->groupBy('materia_id') as $bloques)
                        <span>{{ $bloques->first()->materia->nombre }} <b>{{ $bloques->count() }}</b></span>
                    @endforeach
                </div>
                <div class="form-actions"><a class="secondary-button" href="{{ route('horarios.show', $horario) }}">Ver
                        horario semanal</a></div>
            </article>
        @empty
            <section class="panel schedule-empty">
                <h2>Aún no hay horarios registrados</h2>
                <p>Define el docente responsable, el curso y los bloques de cada materia.</p>
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
                    <a class="primary-button" href="{{ route('horarios.create') }}">Crear el primer horario</a>
                @endif
            </section>
        @endforelse
    </div>
    @if ($horarios->hasPages())
        <div class="pagination"><span>{{ $horarios->total() }} horarios</span>
            <div>
                @if ($horarios->previousPageUrl())
                    <a class="secondary-button" href="{{ $horarios->previousPageUrl() }}">Anterior</a>
                    @endif @if ($horarios->nextPageUrl())
                        <a class="secondary-button" href="{{ $horarios->nextPageUrl() }}">Siguiente</a>
                    @endif
            </div>
        </div>
    @endif
</x-layouts.aplicacion>
