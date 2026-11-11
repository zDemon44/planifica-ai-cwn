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
                            <div class="planning-ai-assistant" data-period="{{ $indice }}">
                                <div class="planning-ai-copy"><span aria-hidden="true">✦</span>
                                    <div><strong>Asistente de planificación</strong>
                                        <p>Genera una propuesta ERCA con DUA a partir de la destreza y el indicador seleccionados.</p>
                                    </div>
                                </div>
                                <button class="planning-ai-button" type="button"
                                    data-endpoint="{{ route('planificaciones.asistente.periodos.store') }}"
                                    data-duration="{{ $bloque['duracion_minutos'] }}" disabled>Generar período con IA</button>
                                <p class="planning-ai-status" role="status" aria-live="polite" hidden></p>
                            </div>
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
                            <p>Registra la técnica aplicada y el instrumento que permitirá recoger la evidencia.</p>
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
                                    name="periodos[{{ $indice }}][instrumento_evaluacion]" rows="3"
                                    placeholder="Ej.: lista de cotejo, rúbrica, cuestionario">{{ old("periodos.$indice.instrumento_evaluacion", $bloque['instrumento_evaluacion'] ?? '') }}</textarea>
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
            const csrf = document.querySelector('.planning-form input[name="_token"]')?.value;
            const asignacionId = {{ (int) ($asignacionSeleccionada?->id ?? 0) }};
            const selectoresCatalogo = new Map();
            const camposGenerados = [
                'tema', 'recursos', 'experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion',
                'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion',
                'tecnica_evaluacion', 'instrumento_evaluacion'
            ];

            const controles = periodo => ({
                destreza: document.getElementById(`periodos_${periodo}_destreza`),
                indicador: document.getElementById(`periodos_${periodo}_indicador`),
                asistente: document.querySelector(`.planning-ai-assistant[data-period="${periodo}"]`)
            });

            const actualizarBotonIa = periodo => {
                const { destreza, indicador, asistente } = controles(periodo);
                const boton = asistente?.querySelector('.planning-ai-button');
                if (boton && !boton.classList.contains('is-loading')) {
                    boton.disabled = !destreza?.value || !indicador?.value;
                }
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
                indicador?.addEventListener('change', () => actualizarBotonIa(destreza.dataset.period));
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

            document.addEventListener('click', evento => {
                if (!evento.target.closest('.planning-catalog-select')) {
                    selectoresCatalogo.forEach(selector => selector.cerrar());
                }
            });
            document.addEventListener('keydown', evento => {
                if (evento.key === 'Escape') selectoresCatalogo.forEach(selector => selector.cerrar());
            });

            document.querySelectorAll('.planning-ai-button').forEach(boton => {
                boton.addEventListener('click', async () => {
                    const asistente = boton.closest('.planning-ai-assistant');
                    const periodo = asistente.dataset.period;
                    const { destreza, indicador } = controles(periodo);
                    const estado = asistente.querySelector('.planning-ai-status');
                    const campos = camposGenerados.map(campo => document.getElementById(
                        `periodos_${periodo}_${campo}`));

                    if (campos.some(campo => campo?.value.trim()) && !window.confirm(
                            'Este bloque ya contiene información. ¿Deseas reemplazarla con una nueva propuesta de la IA?')) {
                        return;
                    }

                    boton.classList.add('is-loading');
                    boton.disabled = true;
                    boton.textContent = 'Generando propuesta…';
                    estado.hidden = false;
                    estado.className = 'planning-ai-status';
                    estado.textContent = 'Ollama está preparando el período. Puede tardar un momento.';

                    try {
                        const respuesta = await fetch(boton.dataset.endpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({
                                asignacion_id: asignacionId,
                                destreza_curricular_id: Number(destreza.value),
                                indicador_evaluacion_id: Number(indicador.value),
                                duracion_minutos: Number(boton.dataset.duration),
                                titulo_unidad: document.getElementById('titulo_unidad')?.value || null
                            })
                        });
                        const datos = await respuesta.json().catch(() => ({}));
                        if (!respuesta.ok) {
                            const primerError = Object.values(datos.errors || {}).flat()[0];
                            throw new Error(primerError || datos.message || 'No se pudo generar la propuesta.');
                        }

                        camposGenerados.forEach(campo => {
                            const entrada = document.getElementById(`periodos_${periodo}_${campo}`);
                            if (entrada && typeof datos.propuesta?.[campo] === 'string') {
                                entrada.value = datos.propuesta[campo];
                                entrada.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        });
                        estado.classList.add('is-success');
                        estado.textContent = datos.message;
                    } catch (error) {
                        estado.classList.add('is-error');
                        estado.textContent = error.message || 'No se pudo generar la propuesta.';
                    } finally {
                        boton.classList.remove('is-loading');
                        boton.textContent = 'Generar otra propuesta';
                        actualizarBotonIa(periodo);
                    }
                });
            });
        })();
    </script>
</x-layouts.aplicacion>
