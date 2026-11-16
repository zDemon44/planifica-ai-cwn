@php
    $rutaListado = route(
        'planificaciones.index',
        $asignacionSeleccionada ? ['asignacion_id' => $asignacionSeleccionada->id] : [],
    );
    $numeroSemanaMiga = old('numero_semana', $semanaSeleccionada['numero'] ?? $planificacion->numero_semana);
    $migasNavegacion = [
        ['label' => 'Planificaciones semanales', 'url' => route('planificaciones.index')],
    ];

    if ($asignacionSeleccionada) {
        $migasNavegacion[] = [
            'label' => $asignacionSeleccionada->materia->nombre,
            'url' => $rutaListado,
        ];
        $migasNavegacion[] = [
            'label' => $numeroSemanaMiga ? "Semana {$numeroSemanaMiga}" : 'Planificación',
            'url' => null,
        ];
    } else {
        $migasNavegacion[] = ['label' => 'Nueva planificación', 'url' => null];
    }
@endphp
<x-layouts.aplicacion :title="$planificacion->exists ? 'Editar planificación' : 'Nueva planificación'"
    :breadcrumbs="$migasNavegacion">
    @php
        $cantidadPeriodos = $bloquesPlanificacion->sum(
            fn($bloque) => $bloque['numero_fin'] - $bloque['numero_inicio'] + 1,
        );
        $trimestreInformativo = old('trimestre', $semanaSeleccionada['trimestre'] ?? $planificacion->trimestre);
        $numeroSemanaInformativo = old('numero_semana', $semanaSeleccionada['numero'] ?? $planificacion->numero_semana);
        $fechaInicioInformativa = old('fecha_inicio', $semanaSeleccionada['inicio'] ?? $planificacion->fecha_inicio?->format('Y-m-d'));
        $fechaFinInformativa = old('fecha_fin', $semanaSeleccionada['fin'] ?? $planificacion->fecha_fin?->format('Y-m-d'));
        $cantidadBloques = $bloquesPlanificacion->count();
        $unidadLibroSeleccionadaId = (int) old('unidad_libro_id', $planificacion->unidad_libro_id ?? 0);
        $unidadLibroSeleccionada = $unidadesLibro->firstWhere('id', $unidadLibroSeleccionadaId);
        $resumenHorario = $cantidadPeriodos
            ? sprintf(
                '%d período%s de 45 min distribuido%s en %d bloque%s.',
                $cantidadPeriodos,
                $cantidadPeriodos === 1 ? '' : 's',
                $cantidadPeriodos === 1 ? '' : 's',
                $cantidadBloques,
                $cantidadBloques === 1 ? '' : 's',
            )
            : 'Esta materia todavía no tiene clases configuradas en el horario.';
    @endphp
    <div class="page-heading planning-form-heading">
        @if ($asignacionSeleccionada)
            <div class="planning-page-context">
                <div class="planning-page-subject">
                    <p class="eyebrow">ASIGNATURA Y CURSO</p>
                    <h1>{{ $asignacionSeleccionada->materia->nombre }}</h1>
                    <p>{{ $asignacionSeleccionada->seccion->etiqueta }}</p>
                </div>
                @if ($numeroSemanaInformativo && $trimestreInformativo && $fechaInicioInformativa && $fechaFinInformativa)
                    <div class="planning-page-week">
                        <strong>Semana {{ $numeroSemanaInformativo }}</strong>
                        <span>Trimestre {{ $trimestreInformativo }}</span>
                        <time>{{ Carbon\Carbon::parse($fechaInicioInformativa)->format('d/m/Y') }} –
                            {{ Carbon\Carbon::parse($fechaFinInformativa)->format('d/m/Y') }}</time>
                    </div>
                @endif
                <p class="planning-page-schedule">{{ $resumenHorario }}</p>
            </div>
        @else
            <div>
                <p class="eyebrow">PLANIFICACIÓN MICROCURRICULAR</p>
                <h1>{{ $planificacion->exists ? 'Editar planificación' : 'Nueva planificación' }}</h1>
                <p>Selecciona una materia para preparar la semana.</p>
            </div>
        @endif
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
                    <p>Completa los datos que dependen del docente.</p>
                </div>
            </div>
            <div class="planning-fields">
                @if ($asignacionSeleccionada)
                    <input type="hidden" name="asignacion_id" value="{{ $asignacionSeleccionada->id }}">
                    <input type="hidden" name="trimestre" value="{{ $trimestreInformativo }}">
                    <input type="hidden" name="numero_semana" value="{{ $numeroSemanaInformativo }}">
                    <input type="hidden" name="fecha_inicio" value="{{ $fechaInicioInformativa }}">
                    <input type="hidden" name="fecha_fin" value="{{ $fechaFinInformativa }}">
                @else
                    <div class="field planning-wide"><label for="asignacion_id">Asignatura · curso · período *</label>
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
                        @error('asignacion_id')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                @endif
                @if ($unidadesLibro->isNotEmpty())
                    <div class="field"><label for="unidad_libro_id">Unidad del libro *</label>
                        <select id="unidad_libro_id" name="unidad_libro_id" required>
                            <option value="">Selecciona una unidad</option>
                            @foreach ($unidadesLibro->groupBy('libro_texto_id') as $unidadesDelLibro)
                                <optgroup label="{{ $unidadesDelLibro->first()->libroTexto->titulo }}">
                                    @foreach ($unidadesDelLibro as $unidad)
                                        <option value="{{ $unidad->id }}" @selected($unidadLibroSeleccionadaId === $unidad->id)>
                                            Unidad {{ $unidad->numero }} · {{ $unidad->titulo }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input id="titulo_unidad" name="titulo_unidad" type="hidden"
                            value="{{ old('titulo_unidad', $unidadLibroSeleccionada?->titulo ?? $planificacion->titulo_unidad) }}">
                        <small class="planning-schedule-hint">Los objetivos y temas se cargarán desde la unidad seleccionada.</small>
                        @error('unidad_libro_id')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                @else
                    <div class="field"><label for="titulo_unidad">Título de la unidad</label><input id="titulo_unidad"
                            name="titulo_unidad" value="{{ old('titulo_unidad', $planificacion->titulo_unidad) }}"
                            maxlength="255" placeholder="Escribe el título de la unidad">
                        @error('titulo_unidad')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                @endif
                <div class="field"><label for="jornada">Jornada *</label><select id="jornada" name="jornada"
                        required>
                        <option value="matutina" @selected(old('jornada', $jornadaSugerida) === 'matutina')>Matutina</option>
                        <option value="vespertina" @selected(old('jornada', $jornadaSugerida) === 'vespertina')>Vespertina</option>
                    </select>
                    @error('jornada')
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
            @if ($unidadesLibro->isNotEmpty())
                <div class="planning-objectives planning-objectives-automatic">
                    <div class="planning-objectives-heading">
                        <div><strong>Objetivos de aprendizaje</strong><small>Se asignan automáticamente según la unidad del libro.</small></div>
                        <span class="planning-automatic-badge">Automático</span>
                    </div>
                    <div id="objetivos-unidad" class="planning-unit-objectives">
                        @forelse($unidadLibroSeleccionada?->objetivos ?? collect() as $objetivo)
                            <article><strong>{{ $objetivo->codigo }}</strong><p>{{ $objetivo->descripcion }}</p></article>
                        @empty
                            <p class="planning-unit-empty">Selecciona una unidad para consultar sus objetivos.</p>
                        @endforelse
                    </div>
                </div>
            @elseif ($asignacionSeleccionada && $objetivosCurriculares->isEmpty())
                <p class="legacy-note">Todavía no hay objetivos curriculares registrados para
                    {{ $asignacionSeleccionada->materia->nombre }} en este subnivel.</p>
            @else
                @php
                    $filasObjetivos = $objetivosSeleccionados->isEmpty()
                        ? collect([null])
                        : $objetivosSeleccionados;
                @endphp
                <div class="planning-objectives">
                    <div class="planning-objectives-heading">
                        <div><strong>Objetivos de aprendizaje</strong><small>Selecciona uno o varios objetivos del
                                catálogo curricular.</small></div>
                        <button class="secondary-button" id="agregar-objetivo" type="button">+ Agregar otro</button>
                    </div>
                    <div id="filas-objetivos">
                        @foreach ($filasObjetivos as $objetivoSeleccionado)
                            <div class="planning-objective-row">
                                <div class="field">
                                    <select id="objetivo_curricular_{{ $loop->index }}"
                                        name="objetivo_curricular_ids[]" aria-label="Objetivo de aprendizaje">
                                        <option value="">Selecciona un objetivo</option>
                                        @foreach ($objetivosCurriculares as $objetivo)
                                            <option value="{{ $objetivo->id }}" @selected((int) $objetivoSeleccionado === $objetivo->id)
                                                title="{{ $objetivo->codigo }} {{ $objetivo->descripcion }}">
                                                {{ $objetivo->codigo }} {{ Str::limit($objetivo->descripcion, 145) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="planning-objective-remove" type="button"
                                    aria-label="Quitar objetivo">×</button>
                            </div>
                        @endforeach
                    </div>
                    @if ($planificacion->exists && $planificacion->objetivos_aprendizaje && $objetivosSeleccionados->isEmpty())
                        <p class="planning-objectives-legacy">Esta planificación conserva objetivos escritos
                            anteriormente. Selecciona objetivos del catálogo para reemplazarlos.</p>
                    @endif
                    @error('objetivo_curricular_ids')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                    @error('objetivo_curricular_ids.*')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>
                <template id="plantilla-objetivo">
                    <div class="planning-objective-row">
                        <div class="field">
                            <select name="objetivo_curricular_ids[]" aria-label="Objetivo de aprendizaje">
                                <option value="">Selecciona un objetivo</option>
                                @foreach ($objetivosCurriculares as $objetivo)
                                    <option value="{{ $objetivo->id }}"
                                        title="{{ $objetivo->codigo }} {{ $objetivo->descripcion }}">
                                        {{ $objetivo->codigo }} {{ Str::limit($objetivo->descripcion, 145) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="planning-objective-remove" type="button" aria-label="Quitar objetivo">×</button>
                    </div>
                </template>
            @endif
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
                            $temaLibroSeleccionado = (int) old(
                                "periodos.$indice.tema_libro_id",
                                $bloque['tema_libro_id'] ?? 0,
                            );
                            $indicadoresDisponibles =
                                $destrezasCurriculares->firstWhere('id', $destrezaSeleccionada)?->indicadores ??
                                collect();
                        @endphp
                        <section class="planning-period-topic">
                            <div class="planning-period-field-heading">
                                <span>01</span>
                                <div><strong>Tema del período</strong><small>Selecciona un tema del libro o escribe uno libre.</small></div>
                            </div>
                            @if ($unidadesLibro->isNotEmpty())
                                <div class="field planning-book-topic">
                                    <label for="periodos_{{ $indice }}_tema_libro">Origen del tema</label>
                                    <select id="periodos_{{ $indice }}_tema_libro"
                                        class="book-topic-select" name="periodos[{{ $indice }}][tema_libro_id]"
                                        data-period="{{ $indice }}" data-selected="{{ $temaLibroSeleccionado }}">
                                        <option value="">Tema libre del docente</option>
                                        @foreach ($unidadLibroSeleccionada?->temas ?? collect() as $temaLibro)
                                            <option value="{{ $temaLibro->id }}" @selected($temaLibroSeleccionado === $temaLibro->id)
                                                @disabled(!$temaLibro->destreza_curricular_id || !$temaLibro->indicador_evaluacion_id)>
                                                {{ $temaLibro->etiqueta }} · {{ $temaLibro->titulo }}{{ !$temaLibro->destreza_curricular_id || !$temaLibro->indicador_evaluacion_id ? ' · Pendiente de catálogo' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="planning-topic-hint">Los temas del libro completan automáticamente la destreza y el indicador.</small>
                                    @error("periodos.$indice.tema_libro_id")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endif
                            <div class="field planning-textarea planning-topic-text">
                                <label for="periodos_{{ $indice }}_tema">Tema</label>
                                <textarea id="periodos_{{ $indice }}_tema" name="periodos[{{ $indice }}][tema]" rows="2"
                                    placeholder="Escribe el tema que se desarrollará" @readonly($temaLibroSeleccionado)>{{ old("periodos.$indice.tema", $bloque['tema'] ?? '') }}</textarea>
                                @error("periodos.$indice.tema")
                                    <small class="field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </section>
                        <div class="planning-period-field-heading planning-period-full">
                            <span>02</span>
                            <div><strong>Selección curricular</strong><small>Relaciona el tema con su destreza e indicador.</small></div>
                        </div>
                        <div class="field planning-curriculum-field"><label
                                for="periodos_{{ $indice }}_destreza">Destreza con criterios de desempeño
                                *</label><select id="periodos_{{ $indice }}_destreza"
                                class="curriculum-skill-select"
                                name="periodos[{{ $indice }}][destreza_curricular_id]"
                                data-period="{{ $indice }}">
                                <option value="">Selecciona una destreza</option>
                                @foreach ($destrezasCurriculares->groupBy('curriculo_id') as $destrezasDelCurriculo)
                                    <optgroup label="{{ $destrezasDelCurriculo->first()->curriculo->nombre }}">
                                        @foreach ($destrezasDelCurriculo as $destreza)
                                            @php
                                                $descripcionDestreza = preg_replace(
                                                    '/^' . preg_quote($destreza->codigo, '/') . '\.?\s*/u',
                                                    '',
                                                    $destreza->descripcion,
                                                );
                                            @endphp
                                            <option value="{{ $destreza->id }}" @selected($destrezaSeleccionada === $destreza->id)>
                                                Grupo {{ $destreza->grupo }} · {{ $descripcionDestreza }}
                                            </option>
                                        @endforeach
                                    </optgroup>
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
                                @foreach ($indicadoresDisponibles->groupBy('curriculo_id') as $indicadoresDelCurriculo)
                                    <optgroup label="{{ $indicadoresDelCurriculo->first()->curriculo->nombre }}">
                                        @foreach ($indicadoresDelCurriculo as $indicador)
                                            @php
                                                $descripcionIndicador = preg_replace(
                                                    '/^' . preg_quote($indicador->codigo, '/') . '\.?\s*/u',
                                                    '',
                                                    $indicador->descripcion,
                                                );
                                            @endphp
                                            <option value="{{ $indicador->id }}" @selected($indicadorSeleccionado === $indicador->id)>
                                                {{ $descripcionIndicador }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error("periodos.$indice.indicador_evaluacion_id")
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                        @if(auth()->user()->role === 'DOCENTE')
                            <div class="planning-ai-launch">
                                <div><span aria-hidden="true">✦</span><div><strong>¿Necesitas ayuda con este período?</strong><small>El copiloto puede preparar una propuesta para que la revises.</small></div></div>
                                <button type="button" data-ai-open="{{ $indice }}">Abrir copiloto</button>
                            </div>
                            <section class="planning-ai-assistant" id="planning-ai-assistant-{{ $indice }}"
                                data-period="{{ $indice }}" role="dialog" aria-modal="true"
                                aria-labelledby="planning-ai-title-{{ $indice }}" aria-hidden="true" inert>
                                <header class="planning-ai-header">
                                    <div class="planning-ai-copy"><span aria-hidden="true">✦</span>
                                        <div><strong id="planning-ai-title-{{ $indice }}">Copiloto del período</strong>
                                            <p>Prepara una propuesta ERCA con DUA para que la revises antes de incorporarla.</p>
                                        </div>
                                    </div>
                                    <div class="planning-ai-header-actions"><span class="planning-ai-badge">IA local</span>
                                        <button class="planning-ai-close" type="button" aria-label="Cerrar copiloto">×</button>
                                    </div>
                                </header>
                                <div class="planning-ai-readiness">
                                    <span aria-hidden="true"></span>
                                    <div><strong class="planning-ai-readiness-title">Selecciona la destreza y el indicador</strong>
                                        <small>{{ $bloque['duracion_minutos'] }} minutos disponibles para este bloque</small>
                                    </div>
                                </div>
                                <div class="field planning-ai-instructions">
                                    <label for="periodos_{{ $indice }}_indicaciones_ia">¿Quieres orientar la propuesta? <small>Opcional</small></label>
                                    <textarea id="periodos_{{ $indice }}_indicaciones_ia" rows="2" maxlength="600"
                                        placeholder="Ej.: utiliza material concreto, trabajo en parejas o actividades sin internet"></textarea>
                                    <small>La IA respetará la selección curricular, el tema y el tiempo del período.</small>
                                </div>
                                <div class="planning-ai-generate">
                                    <button class="planning-ai-button" type="button"
                                        data-endpoint="{{ route('planificaciones.asistente.periodos.store') }}"
                                        data-duration="{{ $bloque['duracion_minutos'] }}" disabled>
                                        <span aria-hidden="true">✦</span><span class="planning-ai-button-label">Preparar propuesta</span>
                                    </button>
                                    <div class="planning-ai-timer" hidden>
                                        <span>Tiempo transcurrido</span>
                                        <strong>00:00</strong>
                                    </div>
                                    <small>La propuesta no modificará el formulario hasta que decidas aplicarla.</small>
                                </div>
                                <p class="planning-ai-status" role="status" aria-live="polite" hidden></p>
                                <div class="planning-ai-preview" hidden>
                                    <header><div><span>Vista previa</span><strong data-ai-preview="tema"></strong></div>
                                        <button class="planning-ai-discard" type="button" aria-label="Descartar propuesta">×</button>
                                    </header>
                                    <div class="planning-ai-preview-meta">
                                        <article><span>Recursos</span><p data-ai-preview="recursos"></p></article>
                                        <article><span>Evaluación</span><p><strong data-ai-preview="tecnica_evaluacion"></strong> · <span data-ai-preview="instrumento_evaluacion"></span></p></article>
                                    </div>
                                    <ol class="planning-ai-preview-erca">
                                        @foreach (['experiencia' => 'Experiencia', 'reflexion' => 'Reflexión', 'conceptualizacion' => 'Conceptualización', 'aplicacion' => 'Aplicación'] as $campoIa => $etiquetaIa)
                                            <li><span>{{ substr($etiquetaIa, 0, 1) }}</span><div><strong>{{ $etiquetaIa }}</strong><p data-ai-preview="{{ $campoIa }}"></p><small><b>DUA:</b> <span data-ai-preview="dua_{{ $campoIa }}"></span></small></div></li>
                                        @endforeach
                                    </ol>
                                    <footer>
                                        <div><strong>¿Cómo deseas incorporarla?</strong><small>Puedes proteger lo que ya escribiste o sustituir todo el contenido del período.</small></div>
                                        <div class="planning-ai-preview-actions">
                                            <button class="planning-ai-apply-empty" type="button">Completar campos vacíos</button>
                                            <button class="planning-ai-apply-all" type="button">Reemplazar período</button>
                                        </div>
                                    </footer>
                                </div>
                            </section>
                        @endif
                        <div class="field planning-textarea planning-period-full"><label
                                for="periodos_{{ $indice }}_recursos">Recursos</label>
                            <textarea id="periodos_{{ $indice }}_recursos" name="periodos[{{ $indice }}][recursos]" rows="3">{{ old("periodos.$indice.recursos", $bloque['recursos'] ?? '') }}</textarea>
                            @error("periodos.$indice.recursos")
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="planning-erca-form">
                            <div class="planning-period-field-heading">
                                <span>03</span>
                                <div><strong>Desarrollo ERCA</strong><small>Describe las actividades y el principio DUA de cada fase.</small></div>
                            </div>
                            @foreach (['experiencia' => 'Experiencia', 'reflexion' => 'Reflexión', 'conceptualizacion' => 'Conceptualización', 'aplicacion' => 'Aplicación'] as $campo => $etiqueta)
                                @php($campoDua = "dua_{$campo}")
                                <section class="planning-erca-phase">
                                    <div class="field planning-textarea">
                                        <label for="periodos_{{ $indice }}_{{ $campo }}">{{ $etiqueta }}</label>
                                        <textarea id="periodos_{{ $indice }}_{{ $campo }}"
                                            name="periodos[{{ $indice }}][{{ $campo }}]" rows="4">{{ old("periodos.$indice.$campo", $bloque[$campo] ?? '') }}</textarea>
                                        @error("periodos.$indice.$campo")
                                            <small class="field-error">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    <div class="field planning-textarea planning-dua-field">
                                        <label for="periodos_{{ $indice }}_{{ $campoDua }}">DUA:</label>
                                        <textarea id="periodos_{{ $indice }}_{{ $campoDua }}"
                                            name="periodos[{{ $indice }}][{{ $campoDua }}]" rows="3"
                                            placeholder="Principio DUA aplicado en {{ Str::lower($etiqueta) }}">{{ old("periodos.$indice.$campoDua", $bloque[$campoDua] ?? '') }}</textarea>
                                        @error("periodos.$indice.$campoDua")
                                            <small class="field-error">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </section>
                            @endforeach
                        </div>
                        <fieldset class="planning-evaluation-fields">
                            <legend>Orientaciones para la evaluación</legend>
                            <p>Registra el método de evaluación y deja el instrumento completo con sus criterios, niveles o preguntas.</p>
                            <div class="field planning-textarea">
                                <label for="periodos_{{ $indice }}_tecnica_evaluacion">Técnica</label>
                                <textarea id="periodos_{{ $indice }}_tecnica_evaluacion"
                                    name="periodos[{{ $indice }}][tecnica_evaluacion]" rows="3"
                                    placeholder="Ej.: observación directa, resolución de problemas">{{ old("periodos.$indice.tecnica_evaluacion", $bloque['tecnica_evaluacion'] ?? '') }}</textarea>
                                @error("periodos.$indice.tecnica_evaluacion")
                                    <small class="field-error">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="field planning-textarea">
                                <label for="periodos_{{ $indice }}_instrumento_evaluacion">Instrumento</label>
                                <textarea id="periodos_{{ $indice }}_instrumento_evaluacion"
                                    name="periodos[{{ $indice }}][instrumento_evaluacion]" rows="6"
                                    placeholder="Ej.: lista de cotejo con escala y criterios observables; rúbrica con niveles; o cuestionario con sus preguntas">{{ old("periodos.$indice.instrumento_evaluacion", $bloque['instrumento_evaluacion'] ?? '') }}</textarea>
                                @error("periodos.$indice.instrumento_evaluacion")
                                    <small class="field-error">{{ $message }}</small>
                                @enderror
                            </div>
                        </fieldset>
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
    @if(auth()->user()->role === 'DOCENTE')
        <button class="planning-ai-backdrop" type="button" aria-label="Cerrar copiloto" hidden></button>
    @endif

    @if (!$planificacion->exists && !$asignacionSeleccionada)
        <script>
            document.getElementById('asignacion_id')?.addEventListener('change', event => {
                if (event.target.value) window.location.assign(event.target.value)
            });
        </script>
    @endif
    <script>
        (() => {
            const filas = document.getElementById('filas-objetivos');
            const plantilla = document.getElementById('plantilla-objetivo');
            const agregar = document.getElementById('agregar-objetivo');
            if (!filas || !plantilla || !agregar) return;

            const actualizar = () => {
                const registros = [...filas.querySelectorAll('.planning-objective-row')];
                const seleccionados = new Set(registros.map(fila => fila.querySelector('select').value).filter(Boolean));
                registros.forEach(fila => {
                    const select = fila.querySelector('select');
                    fila.querySelector('.planning-objective-remove').hidden = registros.length === 1;
                    [...select.options].forEach(opcion => {
                        opcion.disabled = opcion.value !== select.value && seleccionados.has(opcion.value);
                    });
                });
                agregar.disabled = registros.length >= {{ min(10, max(1, $objetivosCurriculares->count())) }};
            };

            agregar.addEventListener('click', () => {
                filas.append(plantilla.content.cloneNode(true));
                actualizar();
            });
            filas.addEventListener('click', event => {
                const boton = event.target.closest('.planning-objective-remove');
                if (!boton) return;
                boton.closest('.planning-objective-row').remove();
                actualizar();
            });
            filas.addEventListener('change', actualizar);
            actualizar();
        })();
    </script>
    <script>
        (() => {
            const relacionados = @json($indicadoresPorDestreza);
            const unidadesLibro = @json($catalogoUnidades);
            let csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('.planning-form input[name="_token"]')?.value;
            const csrfEndpoint = @json(route('planificaciones.asistente.token'));
            const asignacionId = {{ (int) ($asignacionSeleccionada?->id ?? 0) }};
            const selectoresCatalogo = new Map();
            let generacionIaActiva = false;
            const camposGenerados = [
                'tema', 'recursos', 'experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion',
                'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion',
                'tecnica_evaluacion', 'instrumento_evaluacion'
            ];

            const actualizarCsrf = async () => {
                const respuesta = await fetch(csrfEndpoint, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!respuesta.ok || respuesta.redirected) return false;
                const datos = await respuesta.json().catch(() => ({}));
                if (typeof datos.token !== 'string' || !datos.token) return false;

                csrf = datos.token;
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrf);
                document.querySelectorAll('input[name="_token"]').forEach(campo => campo.value = csrf);
                return true;
            };

            const controles = periodo => ({
                destreza: document.getElementById(`periodos_${periodo}_destreza`),
                indicador: document.getElementById(`periodos_${periodo}_indicador`),
                asistente: document.querySelector(`.planning-ai-assistant[data-period="${periodo}"]`)
            });

            const actualizarBotonIa = periodo => {
                const { destreza, indicador, asistente } = controles(periodo);
                const boton = asistente?.querySelector('.planning-ai-button');
                const listo = Boolean(destreza?.value && indicador?.value);
                if (boton && !boton.classList.contains('is-loading')) {
                    boton.disabled = !listo || generacionIaActiva;
                }
                const preparacion = asistente?.querySelector('.planning-ai-readiness');
                const titulo = asistente?.querySelector('.planning-ai-readiness-title');
                if (preparacion && titulo) {
                    preparacion.classList.toggle('is-ready', listo);
                    titulo.textContent = listo
                        ? 'Contexto curricular listo para generar'
                        : 'Selecciona la destreza y el indicador';
                }
            };

            const descartarPropuesta = asistente => {
                if (!asistente) return;
                asistente.propuestaIa = null;
                const vistaPrevia = asistente.querySelector('.planning-ai-preview');
                if (vistaPrevia) vistaPrevia.hidden = true;
            };

            const descripcionSinCodigo = item => {
                const descripcion = item.descripcion.trim();
                const codigoConPunto = `${item.codigo}.`;
                if (descripcion.startsWith(codigoConPunto)) {
                    return descripcion.slice(codigoConPunto.length).trim();
                }
                if (descripcion.startsWith(item.codigo)) {
                    return descripcion.slice(item.codigo.length).replace(/^\.\s*/u, '').trim();
                }
                return descripcion;
            };

            const crearSelectorCatalogo = select => {
                const contenedor = document.createElement('div');
                contenedor.className = `planning-catalog-select ${select.classList.contains('curriculum-indicator-select') ? 'is-indicator' : 'is-skill'}`;
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'planning-catalog-select-trigger';
                boton.setAttribute('aria-haspopup', 'listbox');
                boton.setAttribute('aria-expanded', 'false');
                const texto = document.createElement('span');
                const menu = document.createElement('div');
                menu.className = 'planning-catalog-select-menu';
                menu.setAttribute('role', 'listbox');
                menu.hidden = true;
                boton.append(texto);
                select.before(contenedor);
                contenedor.append(select, boton, menu);
                select.classList.add('planning-catalog-native-select');

                const cerrar = () => {
                    menu.hidden = true;
                    boton.setAttribute('aria-expanded', 'false');
                    contenedor.classList.remove('is-open');
                };

                const reconstruir = () => {
                    menu.replaceChildren();
                    const seleccionada = select.selectedOptions[0];
                    texto.textContent = seleccionada?.textContent.trim() || 'Selecciona una opción';
                    const bloqueadoPorTema = select.dataset.topicLocked === 'true';
                    boton.disabled = select.disabled || bloqueadoPorTema;
                    boton.classList.toggle('is-placeholder', !select.value);

                    [...select.children].forEach(elemento => {
                        if (elemento instanceof HTMLOptGroupElement) {
                            const grupo = document.createElement('section');
                            grupo.className = 'planning-catalog-select-group';
                            const titulo = document.createElement('strong');
                            titulo.textContent = elemento.label;
                            grupo.append(titulo);
                            [...elemento.children].forEach(opcion => grupo.append(crearOpcion(opcion)));
                            menu.append(grupo);
                            return;
                        }
                        if (elemento instanceof HTMLOptionElement) {
                            menu.append(crearOpcion(elemento));
                        }
                    });
                    if (select.disabled || bloqueadoPorTema) cerrar();
                };

                const crearOpcion = opcion => {
                    const elemento = document.createElement('button');
                    elemento.type = 'button';
                    elemento.className = 'planning-catalog-select-option';
                    elemento.setAttribute('role', 'option');
                    elemento.setAttribute('aria-selected', opcion.selected ? 'true' : 'false');
                    elemento.classList.toggle('is-selected', opcion.selected);
                    elemento.classList.toggle('is-placeholder', !opcion.value);
                    elemento.textContent = opcion.textContent.trim();
                    elemento.addEventListener('click', () => {
                        select.value = opcion.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        reconstruir();
                        cerrar();
                        boton.focus();
                    });
                    return elemento;
                };

                boton.addEventListener('click', () => {
                    const abrir = menu.hidden;
                    document.querySelectorAll('.planning-catalog-select.is-open').forEach(abierto => {
                        if (abierto !== contenedor) abierto.querySelector('.planning-catalog-select-trigger')?.click();
                    });
                    menu.hidden = !abrir;
                    boton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
                    contenedor.classList.toggle('is-open', abrir);
                });
                select.addEventListener('change', reconstruir);
                selectoresCatalogo.set(select, { reconstruir, cerrar });
                reconstruir();
            };

            document.querySelectorAll('.curriculum-skill-select').forEach(destreza => {
                destreza.addEventListener('change', () => {
                    descartarPropuesta(controles(destreza.dataset.period).asistente);
                    const indicador = document.getElementById(
                        `periodos_${destreza.dataset.period}_indicador`);
                    const opciones = relacionados[destreza.value] || [];
                    indicador.replaceChildren(new Option(opciones.length ? 'Selecciona un indicador' :
                        'No hay indicadores relacionados', ''));
                    const opcionesPorCurriculo = opciones.reduce((grupos, item) => {
                        if (!grupos.has(item.curriculo)) grupos.set(item.curriculo, []);
                        grupos.get(item.curriculo).push(item);
                        return grupos;
                    }, new Map());
                    opcionesPorCurriculo.forEach((items, curriculo) => {
                        const grupo = document.createElement('optgroup');
                        grupo.label = curriculo;
                        items.forEach(item => grupo.append(new Option(descripcionSinCodigo(item), item.id)));
                        indicador.append(grupo);
                    });
                    indicador.disabled = opciones.length === 0;
                    selectoresCatalogo.get(indicador)?.reconstruir();
                    actualizarBotonIa(destreza.dataset.period);
                });
                const indicador = document.getElementById(`periodos_${destreza.dataset.period}_indicador`);
                indicador?.addEventListener('change', () => {
                    descartarPropuesta(controles(destreza.dataset.period).asistente);
                    actualizarBotonIa(destreza.dataset.period);
                });
                actualizarBotonIa(destreza.dataset.period);
            });

            document.querySelectorAll('.curriculum-skill-select, .curriculum-indicator-select')
                .forEach(crearSelectorCatalogo);

            const selectorUnidad = document.getElementById('unidad_libro_id');
            const tituloUnidad = document.getElementById('titulo_unidad');
            const objetivosUnidad = document.getElementById('objetivos-unidad');

            const unidadActual = () => unidadesLibro[selectorUnidad?.value] || null;

            const mostrarObjetivosUnidad = unidad => {
                if (!objetivosUnidad) return;
                objetivosUnidad.replaceChildren();
                if (!unidad) {
                    const mensaje = document.createElement('p');
                    mensaje.className = 'planning-unit-empty';
                    mensaje.textContent = 'Selecciona una unidad para consultar sus objetivos.';
                    objetivosUnidad.append(mensaje);
                    return;
                }
                unidad.objetivos.forEach(objetivo => {
                    const tarjeta = document.createElement('article');
                    const codigo = document.createElement('strong');
                    const descripcion = document.createElement('p');
                    codigo.textContent = objetivo.codigo;
                    descripcion.textContent = objetivo.descripcion;
                    tarjeta.append(codigo, descripcion);
                    objetivosUnidad.append(tarjeta);
                });
            };

            const aplicarTemaLibro = (selector, limpiarTemaLibre = false) => {
                const periodo = selector.dataset.period;
                const unidad = unidadActual();
                const temaLibro = unidad?.temas.find(tema => String(tema.id) === selector.value && tema.disponible);
                const { destreza, indicador } = controles(periodo);
                const campoTema = document.getElementById(`periodos_${periodo}_tema`);

                destreza.dataset.topicLocked = temaLibro ? 'true' : 'false';
                indicador.dataset.topicLocked = temaLibro ? 'true' : 'false';
                campoTema.readOnly = Boolean(temaLibro);

                if (temaLibro) {
                    campoTema.value = temaLibro.titulo;
                    destreza.value = String(temaLibro.destreza_curricular_id);
                    destreza.dispatchEvent(new Event('change', { bubbles: true }));
                    indicador.value = String(temaLibro.indicador_evaluacion_id);
                    indicador.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (limpiarTemaLibre) {
                    campoTema.value = '';
                    destreza.value = '';
                    destreza.dispatchEvent(new Event('change', { bubbles: true }));
                }

                selectoresCatalogo.get(destreza)?.reconstruir();
                selectoresCatalogo.get(indicador)?.reconstruir();
            };

            const cargarTemasUnidad = (limpiarSeleccion = false) => {
                const unidad = unidadActual();
                document.querySelectorAll('.book-topic-select').forEach(selector => {
                    const seleccionAnterior = limpiarSeleccion ? '' : selector.dataset.selected || selector.value;
                    selector.replaceChildren(new Option('Tema libre del docente', ''));
                    unidad?.temas.forEach(tema => {
                        const sufijo = tema.disponible ? '' : ' · Pendiente de catálogo';
                        const opcion = new Option(`${tema.etiqueta} · ${tema.titulo}${sufijo}`, tema.id);
                        opcion.disabled = !tema.disponible;
                        selector.append(opcion);
                    });
                    selector.value = [...selector.options].some(opcion => opcion.value === String(seleccionAnterior) && !opcion.disabled)
                        ? String(seleccionAnterior)
                        : '';
                    selector.dataset.selected = selector.value;
                    aplicarTemaLibro(selector, limpiarSeleccion);
                });
            };

            document.querySelectorAll('.book-topic-select').forEach(selector => {
                selector.addEventListener('change', () => {
                    selector.dataset.selected = selector.value;
                    aplicarTemaLibro(selector, true);
                });
            });

            if (selectorUnidad) {
                selectorUnidad.addEventListener('change', () => {
                    const unidad = unidadActual();
                    if (tituloUnidad) tituloUnidad.value = unidad?.titulo || '';
                    mostrarObjetivosUnidad(unidad);
                    cargarTemasUnidad(true);
                });
                mostrarObjetivosUnidad(unidadActual());
                cargarTemasUnidad(false);
            }

            const fondoAsistente = document.querySelector('.planning-ai-backdrop');
            let activadorAsistente = null;

            const cerrarAsistente = (devolverFoco = true) => {
                const abierto = document.querySelector('.planning-ai-assistant.is-open');
                if (!abierto) return;
                abierto.classList.remove('is-open');
                abierto.setAttribute('aria-hidden', 'true');
                abierto.inert = true;
                if (fondoAsistente) fondoAsistente.hidden = true;
                if (devolverFoco) activadorAsistente?.focus();
                activadorAsistente = null;
            };

            document.querySelectorAll('[data-ai-open]').forEach(activador => {
                activador.addEventListener('click', () => {
                    cerrarAsistente(false);
                    const asistente = document.querySelector(
                        `.planning-ai-assistant[data-period="${activador.dataset.aiOpen}"]`);
                    if (!asistente) return;
                    activadorAsistente = activador;
                    asistente.inert = false;
                    asistente.setAttribute('aria-hidden', 'false');
                    asistente.classList.add('is-open');
                    if (fondoAsistente) fondoAsistente.hidden = false;
                    asistente.querySelector('.planning-ai-close')?.focus();
                });
            });

            document.querySelectorAll('.planning-ai-close').forEach(boton => {
                boton.addEventListener('click', () => cerrarAsistente());
            });
            fondoAsistente?.addEventListener('click', () => cerrarAsistente());

            document.addEventListener('click', evento => {
                if (!evento.target.closest('.planning-catalog-select')) {
                    selectoresCatalogo.forEach(selector => selector.cerrar());
                }
            });
            document.addEventListener('keydown', evento => {
                if (evento.key === 'Escape') {
                    selectoresCatalogo.forEach(selector => selector.cerrar());
                    cerrarAsistente();
                }
                if (evento.key === 'Tab') {
                    const abierto = document.querySelector('.planning-ai-assistant.is-open');
                    if (!abierto) return;
                    const elementos = [...abierto.querySelectorAll('button:not(:disabled), textarea:not(:disabled)')]
                        .filter(elemento => elemento.getClientRects().length > 0);
                    const primero = elementos[0];
                    const ultimo = elementos[elementos.length - 1];
                    if (evento.shiftKey && document.activeElement === primero) {
                        evento.preventDefault();
                        ultimo?.focus();
                    } else if (!evento.shiftKey && document.activeElement === ultimo) {
                        evento.preventDefault();
                        primero?.focus();
                    }
                }
            });

            document.querySelectorAll('.planning-ai-button').forEach(boton => {
                boton.addEventListener('click', async () => {
                    if (generacionIaActiva) return;
                    generacionIaActiva = true;
                    document.querySelectorAll('.planning-ai-button').forEach(candidato => candidato.disabled = true);
                    const asistente = boton.closest('.planning-ai-assistant');
                    const periodo = asistente.dataset.period;
                    const { destreza, indicador } = controles(periodo);
                    const estado = asistente.querySelector('.planning-ai-status');
                    const etiquetaBoton = boton.querySelector('.planning-ai-button-label');
                    const vistaPrevia = asistente.querySelector('.planning-ai-preview');
                    const contador = asistente.querySelector('.planning-ai-timer');
                    const contadorEtiqueta = contador?.querySelector('span');
                    const contadorValor = contador?.querySelector('strong');
                    const inicio = Date.now();
                    const formatearTiempo = milisegundos => {
                        const segundosTotales = Math.floor(milisegundos / 1000);
                        const minutos = Math.floor(segundosTotales / 60);
                        const segundos = segundosTotales % 60;
                        return `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
                    };
                    const actualizarContador = () => {
                        if (contadorValor) contadorValor.textContent = formatearTiempo(Date.now() - inicio);
                    };
                    const intervaloContador = window.setInterval(actualizarContador, 250);

                    boton.classList.add('is-loading');
                    boton.disabled = true;
                    etiquetaBoton.textContent = 'Preparando propuesta…';
                    if (contador) {
                        contador.hidden = false;
                        contador.classList.add('is-running');
                    }
                    if (contadorEtiqueta) contadorEtiqueta.textContent = 'Tiempo transcurrido';
                    actualizarContador();
                    estado.hidden = false;
                    estado.className = 'planning-ai-status';
                    estado.textContent = 'El copiloto está organizando las actividades ERCA y sus apoyos DUA. Puede tardar un momento.';
                    vistaPrevia.hidden = true;

                    try {
                        const tema = document.getElementById(`periodos_${periodo}_tema`)?.value.trim();
                        const indicaciones = document.getElementById(`periodos_${periodo}_indicaciones_ia`)?.value.trim();
                        const solicitud = {
                            asignacion_id: asignacionId,
                            destreza_curricular_id: Number(destreza.value),
                            indicador_evaluacion_id: Number(indicador.value),
                            duracion_minutos: Number(boton.dataset.duration),
                            titulo_unidad: document.getElementById('titulo_unidad')?.value || null,
                            tema: tema || null,
                            indicaciones: indicaciones || null
                        };
                        const enviar = () => fetch(boton.dataset.endpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(solicitud)
                        });

                        let respuesta = await enviar();
                        if (respuesta.status === 419 && await actualizarCsrf()) {
                            respuesta = await enviar();
                        }
                        if (respuesta.status === 419) {
                            throw new Error('Tu sesión expiró. Copia cualquier texto que quieras conservar y recarga la página para iniciar sesión nuevamente.');
                        }
                        const datos = await respuesta.json().catch(() => ({}));
                        if (!respuesta.ok) {
                            const primerError = Object.values(datos.errors || {}).flat()[0];
                            throw new Error(primerError || datos.message || 'No se pudo generar la propuesta.');
                        }

                        asistente.propuestaIa = datos.propuesta;
                        asistente.querySelectorAll('[data-ai-preview]').forEach(elemento => {
                            const valor = datos.propuesta?.[elemento.dataset.aiPreview];
                            elemento.textContent = typeof valor === 'string' ? valor : '—';
                        });
                        vistaPrevia.hidden = false;
                        estado.classList.add('is-success');
                        estado.textContent = `Propuesta preparada en ${formatearTiempo(Date.now() - inicio)}. Revísala y elige cómo incorporarla al período.`;
                    } catch (error) {
                        descartarPropuesta(asistente);
                        estado.classList.add('is-error');
                        estado.textContent = error instanceof TypeError && /fetch/i.test(error.message)
                            ? 'La conexión con el servidor se interrumpió mientras Ollama generaba la propuesta. Espera a que termine cualquier generación pendiente y vuelve a intentarlo.'
                            : (error.message || 'No se pudo generar la propuesta.');
                    } finally {
                        window.clearInterval(intervaloContador);
                        actualizarContador();
                        contador?.classList.remove('is-running');
                        if (contadorEtiqueta) contadorEtiqueta.textContent = 'Tiempo total';
                        boton.classList.remove('is-loading');
                        etiquetaBoton.textContent = asistente.propuestaIa ? 'Preparar otra propuesta' : 'Preparar propuesta';
                        generacionIaActiva = false;
                        document.querySelectorAll('.planning-ai-assistant').forEach(panel => actualizarBotonIa(panel.dataset.period));
                    }
                });
            });

            document.querySelectorAll('.planning-ai-assistant').forEach(asistente => {
                const periodo = asistente.dataset.period;
                const estado = asistente.querySelector('.planning-ai-status');

                const aplicar = reemplazar => {
                    const propuesta = asistente.propuestaIa;
                    if (!propuesta) return;

                    const camposConContenido = camposGenerados
                        .map(campo => document.getElementById(`periodos_${periodo}_${campo}`))
                        .filter(campo => campo?.value.trim());
                    if (reemplazar && camposConContenido.length && !window.confirm(
                            'Se reemplazará el contenido escrito en este período. ¿Deseas continuar?')) return;

                    let aplicados = 0;
                    let conservados = 0;
                    camposGenerados.forEach(campo => {
                        const entrada = document.getElementById(`periodos_${periodo}_${campo}`);
                        if (!entrada || typeof propuesta[campo] !== 'string') return;
                        if (!reemplazar && entrada.value.trim()) {
                            conservados++;
                            return;
                        }
                        entrada.value = propuesta[campo];
                        entrada.dispatchEvent(new Event('input', { bubbles: true }));
                        aplicados++;
                    });

                    asistente.querySelector('.planning-ai-preview').hidden = true;
                    estado.hidden = false;
                    estado.className = 'planning-ai-status is-success';
                    estado.textContent = reemplazar
                        ? `Propuesta aplicada en ${aplicados} campos. Revísala antes de guardar.`
                        : `Se completaron ${aplicados} campos vacíos${conservados ? ` y se conservaron ${conservados}` : ''}. Revísalos antes de guardar.`;
                };

                asistente.querySelector('.planning-ai-apply-empty')?.addEventListener('click', () => aplicar(false));
                asistente.querySelector('.planning-ai-apply-all')?.addEventListener('click', () => aplicar(true));
                asistente.querySelector('.planning-ai-discard')?.addEventListener('click', () => {
                    descartarPropuesta(asistente);
                    estado.hidden = false;
                    estado.className = 'planning-ai-status';
                    estado.textContent = 'Propuesta descartada. Puedes ajustar las indicaciones y preparar otra.';
                });
            });
        })();
    </script>
</x-layouts.aplicacion>
