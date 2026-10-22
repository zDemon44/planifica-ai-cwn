<x-layouts.aplicacion :title="$planificacion->exists ? 'Editar planificación' : 'Nueva planificación'">
    @php
        $rutaListado = route(
            'planificaciones.index',
            $asignacionSeleccionada ? ['asignacion_id' => $asignacionSeleccionada->id] : [],
        );
        $cantidadPeriodos = $bloquesPlanificacion->sum(
            fn($bloque) => $bloque['numero_fin'] - $bloque['numero_inicio'] + 1,
        );
    @endphp
    <div class="page-heading">
        <div>
            <p class="eyebrow">PLANIFICACIÓN MICROCURRICULAR</p>
            <h1>{{ $planificacion->exists ? 'Editar planificación' : 'Nueva planificación' }}</h1>
            <p>Los bloques se generan desde el horario semanal de la materia.</p>
        </div>
        <a class="secondary-button"
            href="{{ $planificacion->exists ? route('planificaciones.show', $planificacion) : $rutaListado }}">Volver</a>
    </div>
    @if ($errors->any())
        <div class="delete-confirmation" role="alert"><strong>Revisa los campos indicados.</strong>
            <ul>
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($asignaciones->isEmpty())
        <p class="legacy-note">Necesitas una materia asignada en un período abierto para crear una planificación.</p>
    @endif

    <form method="POST"
        action="{{ $planificacion->exists ? route('planificaciones.update', $planificacion) : route('planificaciones.store') }}"
        class="planning-form">
        @csrf
        @if ($planificacion->exists)
            @method('PUT')<input type="hidden" name="version" value="{{ $planificacion->version }}">
        @endif
        <section class="panel">
            <div class="planning-section-heading"><span>01</span>
                <div>
                    <h2>Datos informativos</h2>
                    <p>Información de la semana y asignatura</p>
                </div>
            </div>
            <div class="planning-fields">
                <div class="field planning-wide"><label for="asignacion_id">Asignatura · curso · período *</label>
                    @if ($asignacionSeleccionada)
                        <p class="planning-fixed">
                            <strong>{{ $asignacionSeleccionada->materia->nombre }}</strong><span>{{ $asignacionSeleccionada->seccion->etiqueta }}</span>
                        </p>
                        <input type="hidden" name="asignacion_id" value="{{ $asignacionSeleccionada->id }}">
                        <small class="planning-schedule-hint">
                            @if ($cantidadPeriodos)
                                {{ $cantidadPeriodos }} período{{ $cantidadPeriodos === 1 ? '' : 's' }} de 45 min
                                distribuido{{ $cantidadPeriodos === 1 ? '' : 's' }} en
                                {{ $bloquesPlanificacion->count() }}
                                bloque{{ $bloquesPlanificacion->count() === 1 ? '' : 's' }}.
                            @else
                                Esta materia todavía no tiene clases configuradas en el horario.
                            @endif
                        </small>
                    @else
                        <select id="asignacion_id" required>
                            <option value="">Selecciona una asignación</option>
                            @foreach ($asignaciones as $asignacion)
                                <option
                                    value="{{ route('planificaciones.create', ['asignacion_id' => $asignacion->id]) }}">
                                    {{ $asignacion->materia->nombre }} · {{ $asignacion->seccion->etiqueta }}</option>
                            @endforeach
                        </select>
                        <small class="planning-schedule-hint">Al seleccionar una materia cargaremos sus bloques
                            semanales.</small>
                    @endif
                    @error('asignacion_id')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="field"><label for="trimestre">Trimestre *</label><select id="trimestre" name="trimestre"
                        required @disabled($semanaSeleccionada)>
                        @for ($numero = 1; $numero <= 3; $numero++)
                            <option value="{{ $numero }}" @selected(old('trimestre', $semanaSeleccionada['trimestre'] ?? $planificacion->trimestre) == $numero)>{{ $numero }}
                            </option>
                        @endfor
                    </select>
                    @if($semanaSeleccionada)<input type="hidden" name="trimestre" value="{{ $semanaSeleccionada['trimestre'] }}">@endif
                    @error('trimestre')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="field"><label for="titulo_unidad">Título de la unidad</label><input id="titulo_unidad"
                        name="titulo_unidad" value="{{ old('titulo_unidad', $planificacion->titulo_unidad) }}"
                        maxlength="255" placeholder="Escribe el título de la unidad">
                    @error('titulo_unidad')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="field"><label for="jornada">Jornada *</label><select id="jornada" name="jornada"
                        required>
                        <option value="matutina" @selected(old('jornada', $jornadaSugerida) === 'matutina')>Matutina</option>
                        <option value="vespertina" @selected(old('jornada', $jornadaSugerida) === 'vespertina')>Vespertina</option>
                    </select>
                    @error('jornada')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="field"><label for="numero_semana">Número de semana *</label><input id="numero_semana"
                        name="numero_semana" type="number" min="1" max="53"
                        value="{{ old('numero_semana', $semanaSeleccionada['numero'] ?? $planificacion->numero_semana) }}" required @readonly($semanaSeleccionada)>
                    @error('numero_semana')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="field"><label>Períodos semanales</label>
                    <p class="planning-calculated-value">{{ $cantidadPeriodos ?: 'Pendiente del horario' }}</p>
                </div>
                <div class="field"><label for="fecha_inicio">Fecha de inicio *</label><input id="fecha_inicio"
                        name="fecha_inicio" type="date"
                        value="{{ old('fecha_inicio', $semanaSeleccionada['inicio'] ?? $planificacion->fecha_inicio?->format('Y-m-d')) }}" required @readonly($semanaSeleccionada)>
                    @error('fecha_inicio')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="field"><label for="fecha_fin">Fecha de finalización *</label><input id="fecha_fin"
                        name="fecha_fin" type="date"
                        value="{{ old('fecha_fin', $semanaSeleccionada['fin'] ?? $planificacion->fecha_fin?->format('Y-m-d')) }}" required @readonly($semanaSeleccionada)>
                    @error('fecha_fin')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="planning-section-heading"><span>02</span>
                <div>
                    <h2>Aprendizaje disciplinar</h2>
                    <p>Información general de la semana</p>
                </div>
            </div>
            <div class="field planning-textarea"><label for="objetivos_aprendizaje">Objetivos de aprendizaje</label>
                <textarea id="objetivos_aprendizaje" name="objetivos_aprendizaje" rows="6"
                    placeholder="Registra los objetivos de aprendizaje correspondientes a la semana">{{ old('objetivos_aprendizaje', $planificacion->objetivos_aprendizaje) }}</textarea>
                @error('objetivos_aprendizaje')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </div>
        </section>

        <section class="panel">
            <div class="planning-section-heading"><span>03</span>
                <div>
                    <h2>Desarrollo por bloques</h2>
                    <p>Las horas consecutivas comparten un solo conjunto de actividades.</p>
                </div>
            </div>
            @if ($asignacionSeleccionada && !$grado)
                <p class="legacy-note">El administrador debe configurar el número de grado del curso antes de usar el
                    catálogo curricular.</p>
            @elseif($asignacionSeleccionada && $destrezasCurriculares->isEmpty())
                <p class="legacy-note">Todavía no hay destrezas importadas para
                    {{ $asignacionSeleccionada->materia->nombre }} de {{ $grado }}.º grado.</p>
            @endif
            @error('periodos')
                <p class="field-error planning-period-error">{{ $message }}</p>
            @enderror
            @forelse($bloquesPlanificacion as $indice => $bloque)
                @php
                    $inicio = $bloque['numero_inicio'];
                    $fin = $bloque['numero_fin'];
                    $tituloPeriodo = $inicio === $fin ? "Período {$inicio}" : "Períodos {$inicio} y {$fin}";
                @endphp
                <article class="planning-period-card">
                    <header>
                        <div><span
                                class="planning-period-number">{{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <div>
                                <h3>{{ $tituloPeriodo }} <small>({{ $bloque['duracion_minutos'] }} min)</small></h3>
                                <p>{{ $bloque['dia'] }} ·
                                    @if(!empty($bloque['fecha_clase'])){{ Carbon\Carbon::parse($bloque['fecha_clase'])->format('d/m/Y') }} · @endif
                                    {{ substr($bloque['hora_inicio'], 0, 5) }}–{{ substr($bloque['hora_fin'], 0, 5) }}
                                    @if ($bloque['incluye_recreo'] ?? false)
                                        · incluye recreo
                                    @endif
                                </p>
                            </div>
                        </div><span class="planning-period-badge">{{ $fin - $inicio + 1 }}
                            período{{ $fin === $inicio ? '' : 's' }}</span>
                    </header>
                    <div class="planning-period-fields">
                        @php
                            $destrezaSeleccionada = (int) old(
                                "periodos.$indice.destreza_curricular_id",
                                $bloque['destreza_curricular_id'] ?? 0,
                            );
                            $indicadorSeleccionado = (int) old(
                                "periodos.$indice.indicador_evaluacion_id",
                                $bloque['indicador_evaluacion_id'] ?? 0,
                            );
                            $indicadoresDisponibles =
                                $destrezasCurriculares->firstWhere('id', $destrezaSeleccionada)?->indicadores ??
                                collect();
                        @endphp
                        <div class="field planning-curriculum-field"><label
                                for="periodos_{{ $indice }}_destreza">Destreza con criterios de desempeño
                                *</label><select id="periodos_{{ $indice }}_destreza"
                                class="curriculum-skill-select"
                                name="periodos[{{ $indice }}][destreza_curricular_id]"
                                data-period="{{ $indice }}">
                                <option value="">Selecciona una destreza</option>
                                @foreach ($destrezasCurriculares as $destreza)
                                    <option value="{{ $destreza->id }}" @selected($destrezaSeleccionada === $destreza->id)>Grupo
                                        {{ $destreza->grupo }} · {{ Str::limit($destreza->descripcion, 115) }}
                                    </option>
                                @endforeach
                            </select>
                            @error("periodos.$indice.destreza_curricular_id")
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="field planning-curriculum-field"><label
                                for="periodos_{{ $indice }}_indicador">Indicador de evaluación relacionado
                                *</label><select id="periodos_{{ $indice }}_indicador"
                                class="curriculum-indicator-select"
                                name="periodos[{{ $indice }}][indicador_evaluacion_id]"
                                data-selected="{{ $indicadorSeleccionado }}" @disabled(!$destrezaSeleccionada)>
                                <option value="">
                                    {{ $destrezaSeleccionada ? 'Selecciona un indicador' : 'Selecciona primero una destreza' }}
                                </option>
                                @foreach ($indicadoresDisponibles as $indicador)
                                    <option value="{{ $indicador->id }}" @selected($indicadorSeleccionado === $indicador->id)>
                                        {{ Str::limit($indicador->descripcion, 115) }}
                                    </option>
                                @endforeach
                            </select>
                            @error("periodos.$indice.indicador_evaluacion_id")
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="field planning-textarea planning-period-full"><label
                                for="periodos_{{ $indice }}_recursos">Recursos</label>
                            <textarea id="periodos_{{ $indice }}_recursos" name="periodos[{{ $indice }}][recursos]" rows="3">{{ old("periodos.$indice.recursos", $bloque['recursos'] ?? '') }}</textarea>
                            @error("periodos.$indice.recursos")
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <fieldset class="planning-strategies">
                            <legend>Orientaciones metodológicas para la enseñanza aprendizaje</legend>
                            <p>Organiza el desarrollo del tema con las fases metodológicas del formato institucional.
                            </p>
                            @foreach (['tema' => 'Tema', 'experiencia' => 'Experiencia', 'reflexion' => 'Reflexión', 'conceptualizacion' => 'Conceptualización', 'aplicacion' => 'Aplicación'] as $campo => $etiqueta)
                                <div
                                    class="field planning-textarea {{ $campo === 'tema' ? 'planning-strategy-topic' : '' }}">
                                    <label
                                        for="periodos_{{ $indice }}_{{ $campo }}">{{ $etiqueta }}</label>
                                    <textarea id="periodos_{{ $indice }}_{{ $campo }}"
                                        name="periodos[{{ $indice }}][{{ $campo }}]" rows="{{ $campo === 'tema' ? 2 : 4 }}">{{ old("periodos.$indice.$campo", $bloque[$campo] ?? '') }}</textarea>
                                    @error("periodos.$indice.$campo")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endforeach
                        </fieldset>
                        <div class="field planning-textarea planning-period-full"><label
                                for="periodos_{{ $indice }}_orientaciones_evaluacion">Orientaciones para la
                                evaluación</label>
                            <textarea id="periodos_{{ $indice }}_orientaciones_evaluacion"
                                name="periodos[{{ $indice }}][orientaciones_evaluacion]" rows="4"
                                placeholder="Incluye actividades, técnicas e instrumentos de evaluación">{{ old("periodos.$indice.orientaciones_evaluacion", $bloque['orientaciones_evaluacion'] ?? '') }}</textarea>
                            @error("periodos.$indice.orientaciones_evaluacion")
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </article>
            @empty
                <div class="planning-period-empty"><strong>No hay bloques disponibles.</strong>
                    <p>Configura las clases de esta materia en el horario antes de crear su planificación.</p>
                </div>
            @endforelse
        </section>

        <section class="panel">
            <div class="planning-section-heading"><span>04</span>
                <div>
                    <h2>Adaptaciones curriculares</h2>
                    <p>Completa esta sección si corresponde</p>
                </div>
            </div>
            @foreach ([
        'adaptacion_destreza' => 'Destrezas con criterios de desempeño',
        'adaptacion_indicadores_evaluacion' => 'Indicadores de evaluación',
        'adaptacion_recursos' => 'Recursos',
        'adaptacion_orientaciones_metodologicas' => 'Orientaciones metodológicas para la enseñanza aprendizaje',
        'adaptacion_actividades_evaluacion_formativa' => 'Actividades e instrumentos de evaluación formativa',
    ] as $campo => $etiqueta)
                <div class="field planning-textarea"><label for="{{ $campo }}">{{ $etiqueta }}</label>
                    <textarea id="{{ $campo }}" name="{{ $campo }}" rows="3">{{ old($campo, $planificacion->{$campo}) }}</textarea>
                    @error($campo)
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
            @endforeach
        </section>
        <div class="form-actions"><a class="secondary-button" href="{{ $rutaListado }}">Cancelar</a><button
                class="primary-button" type="submit" @disabled(!$asignacionSeleccionada || $bloquesPlanificacion->isEmpty())>Guardar borrador</button></div>
    </form>

    @if (!$planificacion->exists && !$asignacionSeleccionada)
        <script>
            document.getElementById('asignacion_id')?.addEventListener('change', event => {
                if (event.target.value) window.location.assign(event.target.value)
            });
        </script>
    @endif
    <script>
        (() => {
            const relacionados = @json($indicadoresPorDestreza);
            const resumir = texto => texto.length > 115 ? `${texto.slice(0, 112)}…` : texto;
            document.querySelectorAll('.curriculum-skill-select').forEach(destreza => {
                destreza.addEventListener('change', () => {
                    const indicador = document.getElementById(
                        `periodos_${destreza.dataset.period}_indicador`);
                    const opciones = relacionados[destreza.value] || [];
                    indicador.replaceChildren(new Option(opciones.length ? 'Selecciona un indicador' :
                        'No hay indicadores relacionados', ''));
                    opciones.forEach(item => indicador.add(new Option(resumir(item.descripcion), item.id)));
                    indicador.disabled = opciones.length === 0;
                });
            });
        })();
    </script>
</x-layouts.aplicacion>
