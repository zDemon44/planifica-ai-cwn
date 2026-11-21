@php
    $migasNavegacion = [
        [
            'label' => 'Planificaciones semanales',
            'url' => $asignacionSeleccionada ? route('planificaciones.index') : null,
        ],
    ];

    if ($asignacionSeleccionada) {
        $migasNavegacion[] = [
            'label' => $asignacionSeleccionada->materia->nombre,
            'url' => null,
        ];
    }
@endphp
<x-layouts.aplicacion title="Planificaciones semanales" :breadcrumbs="$migasNavegacion">
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
                    <div class="planning-subject-actions">
                        <a class="primary-button" href="{{ route('planificaciones.index', ['asignacion_id' => $asignacion->id]) }}">Gestionar planificaciones</a>
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
                    @endif
                @endif
            </div>
        </div>
        @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
        @if($asignacionSeleccionada && ! $asignacionSeleccionada->seccion->periodo->cerrado)
            @php
                $hoy = now()->format('Y-m-d');
                $semanasPorTrimestre = $semanasPeriodo->groupBy('trimestre');
                $trimestreActivo = $semanasPeriodo->first(fn (array $semana): bool => $semana['fin'] >= $hoy)['trimestre'] ?? $semanasPorTrimestre->keys()->last();
                $planificadas = $semanasPeriodo->count() - $semanasPendientes->count();
                $atrasadas = $semanasPendientes->filter(fn (array $semana): bool => $semana['fin'] < $hoy)->count();
            @endphp
            <section class="panel planning-weeks" aria-labelledby="semanas-titulo">
                <div class="planning-weeks-header">
                    <div>
                        <h2 id="semanas-titulo">Semanas del período</h2>
                        <p>Solo semanas con clases de esta materia. Al elegir una pendiente, el formulario ya llega con sus fechas y bloques.</p>
                    </div>
                    <dl class="planning-weeks-summary">
                        <div><dt>Planificadas</dt><dd>{{ $planificadas }}<small>/{{ $semanasPeriodo->count() }}</small></dd></div>
                        <div><dt>Pendientes</dt><dd>{{ $semanasPendientes->count() }}</dd></div>
                        @if($atrasadas > 0)<div class="is-late"><dt>Atrasadas</dt><dd>{{ $atrasadas }}</dd></div>@endif
                    </dl>
                </div>
                <div class="planning-weeks-progress" role="progressbar" aria-label="Semanas planificadas" aria-valuemin="0" aria-valuemax="{{ $semanasPeriodo->count() }}" aria-valuenow="{{ $planificadas }}">
                    <span style="width: {{ $semanasPeriodo->count() ? round($planificadas * 100 / $semanasPeriodo->count()) : 0 }}%"></span>
                </div>

                @if($semanasPeriodo->isEmpty())
                    <div class="planning-week-empty">No hay semanas con clases para esta materia. Revisa que el horario y el cronograma estén configurados.</div>
                @else
                    <div class="planning-trimester-tabs">
                        @foreach($semanasPorTrimestre as $trimestre => $semanasTrimestre)
                            <input class="planning-tab-input" type="radio" name="trimestre_semanas" id="trimestre-semanas-{{ $trimestre }}" @checked($trimestre == $trimestreActivo)>
                        @endforeach
                        <div class="planning-tab-list">
                            @foreach($semanasPorTrimestre as $trimestre => $semanasTrimestre)
                                @php
                                    $pendientesTrimestre = $semanasTrimestre->whereNull('planificacion')->count();
                                @endphp
                                <label for="trimestre-semanas-{{ $trimestre }}" class="planning-tab planning-tab-{{ $trimestre }}">
                                    Trimestre {{ $trimestre }}
                                    <small>{{ $pendientesTrimestre === 0 ? 'Completo' : $pendientesTrimestre.' por llenar' }}</small>
                                </label>
                            @endforeach
                        </div>
                        @foreach($semanasPorTrimestre as $trimestre => $semanasTrimestre)
                            <div class="planning-tab-panel planning-tab-panel-{{ $trimestre }}">
                                <ol class="planning-week-calendar">
                                    @foreach($semanasTrimestre as $semana)
                                        @php
                                            $planificacion = $semana['planificacion'];
                                            $inicio = Carbon\Carbon::parse($semana['inicio']);
                                            $fin = Carbon\Carbon::parse($semana['fin']);
                                            $esActual = $inicio->copy()->startOfWeek()->format('Y-m-d') <= $hoy && $inicio->copy()->startOfWeek()->addDays(6)->format('Y-m-d') >= $hoy;
                                            $estadoSemana = $planificacion ? $planificacion->estado : ($semana['fin'] < $hoy ? 'atrasada' : 'pendiente');
                                            $etiquetaEstado = $planificacion ? App\Models\Planificacion::ESTADOS[$planificacion->estado] : ($estadoSemana === 'atrasada' ? 'Atrasada' : 'Por planificar');
                                        @endphp
                                        <li>
                                            <a class="planning-week-tile is-{{ $estadoSemana }} {{ $esActual ? 'is-current' : '' }}"
                                                href="{{ $planificacion ? route('planificaciones.show', $planificacion->id) : route('planificaciones.create', ['asignacion_id' => $asignacionSeleccionada->id, 'fecha_inicio' => $semana['inicio']]) }}"
                                                aria-label="Semana {{ $semana['numero'] }}, del {{ $inicio->locale('es')->isoFormat('D [de] MMMM') }} al {{ $fin->locale('es')->isoFormat('D [de] MMMM') }}: {{ $etiquetaEstado }}{{ $esActual ? ', semana actual' : '' }}">
                                                <span class="planning-week-number"><small>Semana</small>{{ $semana['numero'] }}</span>
                                                <span class="planning-week-dates">{{ $inicio->format('j') }}{{ $inicio->month !== $fin->month ? ' '.$inicio->locale('es')->isoFormat('MMM') : '' }} – {{ $fin->locale('es')->isoFormat('D MMM') }}</span>
                                                <span class="planning-week-state">{{ $esActual && ! $planificacion ? 'Esta semana' : $etiquetaEstado }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endforeach
                    </div>
                    <ul class="planning-week-legend" aria-label="Leyenda de estados">
                        <li><span class="is-pendiente"></span>Por planificar</li>
                        <li><span class="is-atrasada"></span>Atrasada</li>
                        <li><span class="is-borrador"></span>Borrador</li>
                        <li><span class="is-en_revision"></span>En revisión</li>
                        <li><span class="is-devuelta"></span>Devuelta</li>
                        <li><span class="is-aprobada"></span>Aprobada</li>
                        <li><span class="is-current-key"></span>Semana actual</li>
                    </ul>
                @endif
            </section>
        @endif
        <section class="panel record-list">
            @unless($asignacionSeleccionada)
                <form method="GET" action="{{ route('planificaciones.index') }}" class="planning-filters">
                    <div class="field"><label for="periodo_id">Período académico</label><select id="periodo_id" name="periodo_id"><option value="">Todos</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected(($filtros['periodo_id'] ?? '') == $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                    <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>@foreach(App\Models\Planificacion::ESTADOS as $clave => $etiqueta)<option value="{{ $clave }}" @selected(($filtros['estado'] ?? '') === $clave)>{{ $etiqueta }}</option>@endforeach</select></div>
                    <button class="primary-button" type="submit">Filtrar</button>
                </form>
            @endunless
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
                    <tr><td colspan="6">{{ $asignacionSeleccionada ? 'No hay planificaciones registradas para esta materia.' : 'No hay planificaciones con estos filtros.' }}</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="pagination"><span>{{ $planificaciones->total() }} planificaciones</span><div>@if($planificaciones->previousPageUrl())<a class="secondary-button" href="{{ $planificaciones->previousPageUrl() }}">Anterior</a>@endif @if($planificaciones->nextPageUrl())<a class="secondary-button" href="{{ $planificaciones->nextPageUrl() }}">Siguiente</a>@endif</div></div>
        </section>
    @endif
</x-layouts.aplicacion>
