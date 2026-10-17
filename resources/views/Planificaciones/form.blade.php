<x-layouts.aplicacion :title="$planificacion->exists ? 'Editar planificación' : 'Nueva planificación'">
    @php
        $rutaListado = route('planificaciones.index', $asignacionSeleccionada ? ['asignacion_id' => $asignacionSeleccionada->id] : []);
        $cantidadPeriodos = $bloquesPlanificacion->sum(fn ($bloque) => $bloque['numero_fin'] - $bloque['numero_inicio'] + 1);
    @endphp
    <div class="page-heading">
        <div><p class="eyebrow">PLAN MICROCURRICULAR SEMANAL</p><h1>{{ $planificacion->exists ? 'Editar planificación' : 'Nueva planificación' }}</h1><p>Los bloques se generan desde el horario semanal de la materia.</p></div>
        <a class="secondary-button" href="{{ $planificacion->exists ? route('planificaciones.show', $planificacion) : $rutaListado }}">Volver</a>
    </div>
    @if($errors->any())<div class="delete-confirmation" role="alert"><strong>Revisa los campos indicados.</strong><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    @if($asignaciones->isEmpty())<p class="legacy-note">Necesitas una materia asignada en un período abierto para crear una planificación.</p>@endif

    <form method="POST" action="{{ $planificacion->exists ? route('planificaciones.update', $planificacion) : route('planificaciones.store') }}" class="planning-form">
        @csrf
        @if($planificacion->exists)@method('PUT')<input type="hidden" name="version" value="{{ $planificacion->version }}">@endif
        <section class="panel">
            <div class="planning-section-heading"><span>01</span><div><h2>Datos informativos</h2><p>Información de la semana y asignatura</p></div></div>
            <div class="planning-fields">
                <div class="field planning-wide"><label for="asignacion_id">Asignatura · curso · período *</label>
                    @if($asignacionSeleccionada)
                        <p class="planning-fixed"><strong>{{ $asignacionSeleccionada->materia->nombre }}</strong><span>{{ $asignacionSeleccionada->seccion->etiqueta }}</span></p>
                        <input type="hidden" name="asignacion_id" value="{{ $asignacionSeleccionada->id }}">
                        <small class="planning-schedule-hint">
                            @if($cantidadPeriodos)
                                {{ $cantidadPeriodos }} período{{ $cantidadPeriodos === 1 ? '' : 's' }} de 45 min distribuido{{ $cantidadPeriodos === 1 ? '' : 's' }} en {{ $bloquesPlanificacion->count() }} bloque{{ $bloquesPlanificacion->count() === 1 ? '' : 's' }}.
                            @else
                                Esta materia todavía no tiene clases configuradas en el horario.
                            @endif
                        </small>
                    @else
                        <select id="asignacion_id" required>
                            <option value="">Selecciona una asignación</option>
                            @foreach($asignaciones as $asignacion)
                                <option value="{{ route('planificaciones.create', ['asignacion_id' => $asignacion->id]) }}">{{ $asignacion->materia->nombre }} · {{ $asignacion->seccion->etiqueta }}</option>
                            @endforeach
                        </select>
                        <small class="planning-schedule-hint">Al seleccionar una materia cargaremos sus bloques semanales.</small>
                    @endif
                    @error('asignacion_id')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field"><label for="area">Área</label><input id="area" name="area" value="{{ old('area', $planificacion->area) }}" maxlength="120" placeholder="Ej. Ciencias Sociales">@error('area')<small class="field-error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="trimestre">Trimestre *</label><select id="trimestre" name="trimestre" required>@for($numero = 1; $numero <= 3; $numero++)<option value="{{ $numero }}" @selected(old('trimestre', $planificacion->trimestre) == $numero)>{{ $numero }}</option>@endfor</select>@error('trimestre')<small class="field-error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="numero_semana">Número de semana *</label><input id="numero_semana" name="numero_semana" type="number" min="1" max="53" value="{{ old('numero_semana', $planificacion->numero_semana) }}" required>@error('numero_semana')<small class="field-error">{{ $message }}</small>@enderror</div>
                <div class="field"><label>Períodos semanales</label><p class="planning-calculated-value">{{ $cantidadPeriodos ?: 'Pendiente del horario' }}</p></div>
                <div class="field"><label for="fecha_inicio">Fecha de inicio *</label><input id="fecha_inicio" name="fecha_inicio" type="date" value="{{ old('fecha_inicio', $planificacion->fecha_inicio?->format('Y-m-d')) }}" required>@error('fecha_inicio')<small class="field-error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="fecha_fin">Fecha de finalización *</label><input id="fecha_fin" name="fecha_fin" type="date" value="{{ old('fecha_fin', $planificacion->fecha_fin?->format('Y-m-d')) }}" required>@error('fecha_fin')<small class="field-error">{{ $message }}</small>@enderror</div>
            </div>
        </section>

        <section class="panel">
            <div class="planning-section-heading"><span>02</span><div><h2>Aprendizaje disciplinar</h2><p>Información general de la semana</p></div></div>
            @foreach(['objetivo_disciplinar' => 'Objetivo disciplinar', 'estandar_aprendizaje' => 'Estándar de aprendizaje'] as $campo => $etiqueta)
                <div class="field planning-textarea"><label for="{{ $campo }}">{{ $etiqueta }}</label><textarea id="{{ $campo }}" name="{{ $campo }}" rows="4">{{ old($campo, $planificacion->{$campo}) }}</textarea>@error($campo)<small class="field-error">{{ $message }}</small>@enderror</div>
            @endforeach
        </section>

        <section class="panel">
            <div class="planning-section-heading"><span>03</span><div><h2>Desarrollo por bloques</h2><p>Las horas consecutivas comparten un solo conjunto de actividades.</p></div></div>
            @error('periodos')<p class="field-error planning-period-error">{{ $message }}</p>@enderror
            @forelse($bloquesPlanificacion as $indice => $bloque)
                @php
                    $inicio = $bloque['numero_inicio'];
                    $fin = $bloque['numero_fin'];
                    $tituloPeriodo = $inicio === $fin ? "Período {$inicio}" : "Períodos {$inicio} y {$fin}";
                @endphp
                <article class="planning-period-card">
                    <header><div><span class="planning-period-number">{{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $tituloPeriodo }} <small>({{ $bloque['duracion_minutos'] }} min)</small></h3><p>{{ $bloque['dia'] }} · {{ substr($bloque['hora_inicio'], 0, 5) }}–{{ substr($bloque['hora_fin'], 0, 5) }} @if($bloque['incluye_recreo'] ?? false) · incluye recreo @endif</p></div></div><span class="planning-period-badge">{{ $fin - $inicio + 1 }} período{{ $fin === $inicio ? '' : 's' }}</span></header>
                    <div class="planning-period-fields">
                        @foreach([
                            'destreza_desempeno' => 'Destreza con criterio de desempeño',
                            'indicadores_logro' => 'Indicadores de logro',
                            'recursos' => 'Recursos',
                        ] as $campo => $etiqueta)
                            <div class="field planning-textarea"><label for="periodos_{{ $indice }}_{{ $campo }}">{{ $etiqueta }}</label><textarea id="periodos_{{ $indice }}_{{ $campo }}" name="periodos[{{ $indice }}][{{ $campo }}]" rows="3">{{ old("periodos.$indice.$campo", $bloque[$campo] ?? '') }}</textarea>@error("periodos.$indice.$campo")<small class="field-error">{{ $message }}</small>@enderror</div>
                        @endforeach
                        <fieldset class="planning-strategies">
                            <legend>Estrategias metodológicas</legend>
                            <p>Describe cómo se desarrollará el tema durante este bloque.</p>
                            @foreach(['tema' => 'Tema', 'anticipacion' => 'Anticipación', 'construccion' => 'Construcción', 'consolidacion' => 'Consolidación'] as $campo => $etiqueta)
                                <div class="field planning-textarea {{ $campo === 'tema' ? 'planning-strategy-topic' : '' }}"><label for="periodos_{{ $indice }}_{{ $campo }}">{{ $etiqueta }}</label><textarea id="periodos_{{ $indice }}_{{ $campo }}" name="periodos[{{ $indice }}][{{ $campo }}]" rows="{{ $campo === 'tema' ? 2 : 4 }}">{{ old("periodos.$indice.$campo", $bloque[$campo] ?? '') }}</textarea>@error("periodos.$indice.$campo")<small class="field-error">{{ $message }}</small>@enderror</div>
                            @endforeach
                        </fieldset>
                        <div class="field planning-textarea planning-period-full"><label for="periodos_{{ $indice }}_tecnicas_evaluacion">Técnicas / instrumentos de evaluación</label><textarea id="periodos_{{ $indice }}_tecnicas_evaluacion" name="periodos[{{ $indice }}][tecnicas_evaluacion]" rows="3">{{ old("periodos.$indice.tecnicas_evaluacion", $bloque['tecnicas_evaluacion'] ?? '') }}</textarea>@error("periodos.$indice.tecnicas_evaluacion")<small class="field-error">{{ $message }}</small>@enderror</div>
                    </div>
                </article>
            @empty
                <div class="planning-period-empty"><strong>No hay bloques disponibles.</strong><p>Configura las clases de esta materia en el horario antes de crear su planificación.</p></div>
            @endforelse
        </section>

        <section class="panel">
            <div class="planning-section-heading"><span>04</span><div><h2>Adaptaciones curriculares</h2><p>Completa esta sección si corresponde</p></div></div>
            @foreach([
                'adaptacion_especificacion' => 'Especifique la adaptación',
                'adaptacion_destreza' => 'Destreza con criterio de desempeño',
                'adaptacion_indicadores' => 'Indicadores de logro',
                'adaptacion_recursos' => 'Recursos',
                'adaptacion_estrategias' => 'Estrategias metodológicas',
                'adaptacion_evaluacion' => 'Técnicas e instrumentos de evaluación',
            ] as $campo => $etiqueta)
                <div class="field planning-textarea"><label for="{{ $campo }}">{{ $etiqueta }}</label><textarea id="{{ $campo }}" name="{{ $campo }}" rows="3">{{ old($campo, $planificacion->{$campo}) }}</textarea>@error($campo)<small class="field-error">{{ $message }}</small>@enderror</div>
            @endforeach
        </section>
        <div class="form-actions"><a class="secondary-button" href="{{ $rutaListado }}">Cancelar</a><button class="primary-button" type="submit" @disabled(! $asignacionSeleccionada || $bloquesPlanificacion->isEmpty())>Guardar borrador</button></div>
    </form>

    @if(! $planificacion->exists && ! $asignacionSeleccionada)
        <script>document.getElementById('asignacion_id')?.addEventListener('change',event=>{if(event.target.value)window.location.assign(event.target.value)});</script>
    @endif
</x-layouts.aplicacion>
