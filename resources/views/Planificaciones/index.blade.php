<x-layouts.aplicacion title="Planificaciones semanales">
    @if(auth()->user()->role === 'DOCENTE' && ! $asignacionSeleccionada)
        <div class="page-heading">
            <div><p class="eyebrow">PLAN MICROCURRICULAR</p><h1>Mis planificaciones</h1><p>Selecciona una materia para consultar y preparar sus planificaciones semanales.</p></div>
        </div>
        <section class="planning-subject-grid" aria-label="Materias asignadas">
            @forelse($asignaciones as $asignacion)
                <article class="panel planning-subject-card">
                    <div class="planning-subject-card-top">
                        <span class="course-badge">{{ $asignacion->seccion->periodo->nombre }}</span>
                        <span class="planning-status {{ $asignacion->seccion->periodo->cerrado ? '' : 'planning-status-aprobada' }}">{{ $asignacion->seccion->periodo->cerrado ? 'Histórico' : 'Período abierto' }}</span>
                    </div>
                    <div>
                        <p class="eyebrow">ASIGNATURA</p>
                        <h2>{{ $asignacion->materia->nombre }}</h2>
                        <p>{{ $asignacion->seccion->curso->nombre }} · {{ $asignacion->seccion->nombre }}</p>
                    </div>
                    <div class="planning-subject-summary"><span>Planificaciones registradas</span><strong>{{ $asignacion->planificaciones_count }}</strong></div>
                    <div class="planning-subject-actions">
                        <a class="secondary-button" href="{{ route('planificaciones.index', ['asignacion_id' => $asignacion->id]) }}">Ver planificaciones</a>
                        @unless($asignacion->seccion->periodo->cerrado)<a class="primary-button" href="{{ route('planificaciones.create', ['asignacion_id' => $asignacion->id]) }}">+ Nueva</a>@endunless
                    </div>
                </article>
            @empty
                <section class="panel planning-subject-empty"><h2>Aún no tienes materias asignadas</h2><p>Cuando Administración asigne tus materias, aparecerán aquí.</p></section>
            @endforelse
        </section>
    @else
        <div class="page-heading">
            <div>
                <p class="eyebrow">PLAN MICROCURRICULAR</p>
                <h1>{{ $asignacionSeleccionada?->materia->nombre ?? 'Planificaciones semanales' }}</h1>
                <p>
                    @if($asignacionSeleccionada)
                        {{ $asignacionSeleccionada->seccion->curso->nombre }} · {{ $asignacionSeleccionada->seccion->nombre }} · {{ $asignacionSeleccionada->seccion->periodo->nombre }}
                    @else
                        Revisa y aprueba las planificaciones enviadas.
                    @endif
                </p>
            </div>
            <div class="planning-toolbar-actions">
                @if($asignacionSeleccionada)<a class="secondary-button" href="{{ route('planificaciones.index') }}">Mis materias</a>@endif
                @if($asignacionSeleccionada && ! $asignacionSeleccionada->seccion->periodo->cerrado)
                    @if($semanasPendientes->isNotEmpty())
                        <a class="primary-button" href="{{ route('planificaciones.create', ['asignacion_id' => $asignacionSeleccionada->id, 'fecha_inicio' => $semanasPendientes->first()['inicio']]) }}">Completar siguiente semana</a>
                    @else
                        <a class="primary-button" href="{{ route('planificaciones.create', ['asignacion_id' => $asignacionSeleccionada->id]) }}">+ Nueva planificación</a>
                    @endif
                @endif
            </div>
        </div>
        @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
        @if($asignacionSeleccionada && ! $asignacionSeleccionada->seccion->periodo->cerrado)
            <section class="panel planning-pending-weeks">
                <div class="panel-heading"><div><h2>Planificaciones por llenar</h2><p>Se muestran únicamente semanas con clases. Los días suspendidos ya están excluidos de sus bloques.</p></div><span class="planning-pending-total">{{ $semanasPendientes->count() }} pendientes</span></div>
                <div class="planning-week-grid">
                    @forelse($semanasPendientes as $semana)
                        <a href="{{ route('planificaciones.create', ['asignacion_id' => $asignacionSeleccionada->id, 'fecha_inicio' => $semana['inicio']]) }}"><span>Semana {{ $semana['numero'] }}</span><strong>{{ Carbon\Carbon::parse($semana['inicio'])->format('d/m') }} – {{ Carbon\Carbon::parse($semana['fin'])->format('d/m/Y') }}</strong><small>Trimestre {{ $semana['trimestre'] }} · {{ $semana['bloques']->count() }} bloques por llenar</small></a>
                    @empty
                        <div class="planning-week-empty">No quedan semanas lectivas pendientes para esta materia.</div>
                    @endforelse
                </div>
            </section>
        @endif
        <section class="panel record-list">
            <form method="GET" action="{{ route('planificaciones.index') }}" class="planning-filters">
                @if($asignacionSeleccionada)<input type="hidden" name="asignacion_id" value="{{ $asignacionSeleccionada->id }}">@endif
                @unless($asignacionSeleccionada)
                    <div class="field"><label for="periodo_id">Período académico</label><select id="periodo_id" name="periodo_id"><option value="">Todos</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected(($filtros['periodo_id'] ?? '') == $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                @endunless
                <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>@foreach(App\Models\Planificacion::ESTADOS as $clave => $etiqueta)<option value="{{ $clave }}" @selected(($filtros['estado'] ?? '') === $clave)>{{ $etiqueta }}</option>@endforeach</select></div>
                <button class="primary-button" type="submit">Filtrar</button>
            </form>
            <div class="table-scroll"><table>
                <thead><tr><th>Semana</th><th>Asignatura y curso</th><th>Docente</th><th>Fechas</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse($planificaciones as $planificacion)
                    <tr>
                        <td><strong>Semana {{ $planificacion->numero_semana }}</strong><small>Trimestre {{ $planificacion->trimestre }}</small></td>
                        <td><strong>{{ $planificacion->asignacion->materia->nombre }}</strong><small>{{ $planificacion->asignacion->seccion->curso->nombre }} · {{ $planificacion->asignacion->seccion->nombre }} · {{ $planificacion->asignacion->seccion->periodo->nombre }}</small></td>
                        <td>{{ $planificacion->asignacion->docente->nombres }} {{ $planificacion->asignacion->docente->apellidos }}</td>
                        <td>{{ $planificacion->fecha_inicio->format('d/m/Y') }} – {{ $planificacion->fecha_fin->format('d/m/Y') }}</td>
                        <td><span class="planning-status planning-status-{{ $planificacion->estado }}">{{ App\Models\Planificacion::ESTADOS[$planificacion->estado] }}</span></td>
                        <td><a class="secondary-button" href="{{ route('planificaciones.show', $planificacion) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay planificaciones con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="pagination"><span>{{ $planificaciones->total() }} planificaciones</span><div>@if($planificaciones->previousPageUrl())<a class="secondary-button" href="{{ $planificaciones->previousPageUrl() }}">Anterior</a>@endif @if($planificaciones->nextPageUrl())<a class="secondary-button" href="{{ $planificaciones->nextPageUrl() }}">Siguiente</a>@endif</div></div>
        </section>
    @endif
</x-layouts.aplicacion>
