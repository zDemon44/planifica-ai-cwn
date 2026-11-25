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
        <section class="panel planning-overview-panel">
            <div class="planning-section-heading"><span>01</span>
                <div>
                    <h2>Información de la planificación</h2>
                    <p>Selecciona la unidad y revisa los objetivos de aprendizaje de la semana.</p>
                </div>
            </div>
            <div class="planning-fields planning-info-fields">
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
            </div>

            <div class="planning-subsection-heading">
                <span aria-hidden="true"></span>
                <div>
                    <h3>Aprendizaje disciplinar</h3>
                    <p>Objetivos que orientarán el trabajo de la semana.</p>
                </div>
            </div>
            @if ($unidadesLibro->isNotEmpty())
                <div class="planning-objectives planning-objectives-automatic">
                    <div class="planning-objectives-heading">
                        <div><strong>Objetivos de aprendizaje</strong><small>Se asignan automáticamente según la unidad del libro.</small></div>
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
            <div class="planning-section-heading"><span>02</span>
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
                <article class="planning-period-card" id="bloque-{{ $indice }}" data-period="{{ $indice }}">
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
                        </div>
                        <div class="planning-period-header-actions">
                            @if(auth()->user()->role === 'DOCENTE')
                                <button class="copilot-block-trigger" type="button" data-ai-open="{{ $indice }}"
                                    title="Abre el copiloto para este bloque. También puedes usar ✦ junto a cada campo." aria-label="Abrir el copiloto para el bloque {{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }}">
                                    <span aria-hidden="true">✦</span> Copiloto
                                </button>
                            @endif
                            <span class="planning-period-badge">{{ $fin - $inicio + 1 }}
                                período{{ $fin === $inicio ? '' : 's' }}</span>
                        </div>
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
                            $temaLibroActual = $temaLibroSeleccionado
                                ? $unidadLibroSeleccionada?->temas->firstWhere('id', $temaLibroSeleccionado)
                                : null;
                            $usaLibro = $unidadesLibro->isNotEmpty();
                            $esTaller = $usaLibro && (old("periodos.$indice.tema_libro_id") === 'taller'
                                || (bool) old("periodos.$indice.es_taller", $bloque['es_taller'] ?? false));
                            $temasTaller = collect(old("periodos.$indice.temas_taller", $bloque['temas_taller'] ?? []))
                                ->map(fn ($id): int => (int) $id)->all();
                        @endphp
                        <section class="planning-period-topic {{ $usaLibro ? 'has-book' : '' }}">
                            <div class="planning-period-field-heading">
                                <span>01</span>
                                <div><strong>Tema del período</strong><small>{{ $usaLibro ? 'Elige un tema de la unidad, arma un taller o escribe uno propio.' : 'Escribe el tema que se desarrollará en este bloque.' }}</small></div>
                            </div>
                            @if ($usaLibro)
                                <div class="field planning-book-topic">
                                    <label for="periodos_{{ $indice }}_tema_libro">Tema de la unidad</label>
                                    <select id="periodos_{{ $indice }}_tema_libro"
                                        class="book-topic-select" name="periodos[{{ $indice }}][tema_libro_id]"
                                        data-period="{{ $indice }}" data-selected="{{ $esTaller ? 'taller' : $temaLibroSeleccionado }}">
                                        <option value="">Tema libre</option>
                                        <option value="taller" @selected($esTaller)>Taller · varios temas del libro</option>
                                        @foreach ($unidadLibroSeleccionada?->temas ?? collect() as $temaLibro)
                                            <option value="{{ $temaLibro->id }}" @selected($temaLibroSeleccionado === $temaLibro->id)
                                                @disabled(!$temaLibro->destreza_curricular_id || !$temaLibro->indicador_evaluacion_id)>
                                                {{ $temaLibro->etiqueta }} · {{ $temaLibro->titulo }}{{ !$temaLibro->destreza_curricular_id || !$temaLibro->indicador_evaluacion_id ? ' · Pendiente de catálogo' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("periodos.$indice.tema_libro_id")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="planning-workshop" id="periodos_{{ $indice }}_taller" data-period="{{ $indice }}" @unless ($esTaller) hidden @endunless>
                                    <div class="planning-workshop-heading">
                                        <strong>Temas del taller</strong>
                                        <span data-workshop-total>{{ count($temasTaller) === 1 ? '1 tema' : count($temasTaller).' temas' }}</span>
                                    </div>
                                    <div class="planning-workshop-quick" hidden>
                                        <small>Elegidos en bloques anteriores</small>
                                        <div data-workshop-quick></div>
                                    </div>
                                    <div class="planning-workshop-units">
                                        @foreach ($unidadesLibro as $unidadTaller)
                                            @php
                                                $marcadosUnidad = $unidadTaller->temas->whereIn('id', $temasTaller)->count();
                                            @endphp
                                            <details class="planning-workshop-unit" @if ($marcadosUnidad || (!$temasTaller && $unidadTaller->id === $unidadLibroSeleccionada?->id)) open @endif>
                                                <summary><span>Unidad {{ $unidadTaller->numero }} · {{ $unidadTaller->titulo }}</span><small data-workshop-count>{{ $marcadosUnidad ?: '' }}</small></summary>
                                                <div class="planning-workshop-topics">
                                                    @foreach ($unidadTaller->temas as $temaTaller)
                                                        <label class="planning-workshop-topic">
                                                            <input type="checkbox" name="periodos[{{ $indice }}][temas_taller][]" value="{{ $temaTaller->id }}"
                                                                data-destreza="{{ $temaTaller->destreza_curricular_id }}" data-indicador="{{ $temaTaller->indicador_evaluacion_id }}"
                                                                data-titulo="{{ $temaTaller->titulo }}" data-etiqueta="U{{ $unidadTaller->numero }} · {{ $temaTaller->etiqueta }}"
                                                                @checked(in_array($temaTaller->id, $temasTaller, true)) @disabled(!$esTaller)>
                                                            <span><b>{{ $temaTaller->etiqueta }}</b> {{ $temaTaller->titulo }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                    <div class="planning-workshop-conflict" role="group" aria-labelledby="periodos_{{ $indice }}_taller_choque" hidden>
                                        <strong id="periodos_{{ $indice }}_taller_choque">Los temas tienen destrezas o indicadores distintos</strong>
                                        <p>Elige con cuál quedarte para este bloque:</p>
                                        <div data-workshop-options></div>
                                    </div>
                                    @error("periodos.$indice.temas_taller")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endif
                            <div class="field planning-textarea planning-topic-text" id="periodos_{{ $indice }}_tema_libre"
                                @if ($usaLibro && $temaLibroActual && !$esTaller) hidden @endif>
                                <label for="periodos_{{ $indice }}_tema">{{ $esTaller ? 'Título del taller' : ($usaLibro ? 'Escribe tu tema' : 'Tema') }}</label>
                                <textarea id="periodos_{{ $indice }}_tema" name="periodos[{{ $indice }}][tema]" rows="2"
                                    placeholder="Ej.: Resolución de problemas con sumas de tres cifras">{{ old("periodos.$indice.tema", $bloque['tema'] ?? '') }}</textarea>
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
            <div class="planning-section-heading"><span>03</span>
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
    @if(auth()->user()->role === 'DOCENTE' && $bloquesPlanificacion->isNotEmpty())
        <aside class="copilot" id="copilot" aria-labelledby="copilot-titulo" hidden>
            <header class="copilot-header">
                <span class="copilot-mark" aria-hidden="true">✦</span>
                <div><strong id="copilot-titulo">Copiloto</strong><p>Prepara propuestas ERCA y DUA que revisas en cada campo antes de conservarlas.</p></div>
                <button class="copilot-close" type="button" aria-label="Cerrar copiloto">×</button>
            </header>
            <div class="copilot-body">
                <div class="copilot-field">
                    <label for="copilot-bloque">Bloque</label>
                    <select id="copilot-bloque">
                        @foreach ($bloquesPlanificacion as $indice => $bloque)
                            <option value="{{ $indice }}" data-duration="{{ $bloque['duracion_minutos'] }}">
                                Bloque {{ str_pad($indice + 1, 2, '0', STR_PAD_LEFT) }} · {{ $bloque['dia'] }} {{ substr($bloque['hora_inicio'], 0, 5) }} · {{ $bloque['duracion_minutos'] }} min
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="copilot-readiness">
                    <span aria-hidden="true"></span>
                    <div><strong data-copilot-ready-title>Selecciona la destreza y el indicador</strong><small data-copilot-ready-detail></small></div>
                </div>
                <div class="copilot-field">
                    <label for="copilot-enfoque">¿Qué quieres preparar?</label>
                    <select id="copilot-enfoque">
                        <option value="">Todo el bloque</option>
                        <option value="recursos">Recursos</option>
                        <optgroup label="Desarrollo ERCA">
                            @foreach (['experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion', 'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion'] as $campoEnfoque)
                                <option value="{{ $campoEnfoque }}">{{ App\Services\GeneradorPeriodoPlanificacion::ETIQUETAS[$campoEnfoque] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Evaluación">
                            <option value="tecnica_evaluacion">Técnica de evaluación</option>
                            <option value="instrumento_evaluacion">Instrumento de evaluación</option>
                        </optgroup>
                    </select>
                </div>
                <div class="copilot-field">
                    <label for="copilot-indicaciones">Indicaciones <small>Opcional</small></label>
                    <textarea id="copilot-indicaciones" rows="3" maxlength="600"
                        placeholder="Ej.: utiliza material concreto, trabajo en parejas o actividades sin internet"></textarea>
                </div>
                <div class="copilot-actions">
                    <button class="copilot-generate" type="button" data-endpoint="{{ route('planificaciones.asistente.periodos.store') }}" disabled>
                        <span aria-hidden="true">✦</span><span data-copilot-label>Preparar propuesta</span>
                    </button>
                    <span class="copilot-timer" aria-label="Tiempo transcurrido" hidden>00:00</span>
                </div>
                <p class="copilot-status" role="status" aria-live="polite" hidden></p>
                <section class="copilot-changes" aria-labelledby="copilot-cambios-titulo" hidden>
                    <header><strong id="copilot-cambios-titulo">Cambios propuestos</strong><span data-copilot-count></span></header>
                    <ul></ul>
                    <footer>
                        <button class="copilot-discard-all" type="button">Descartar todo</button>
                        <button class="copilot-keep-all" type="button">Mantener todo</button>
                    </footer>
                </section>
            </div>
        </aside>
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
                indicador: document.getElementById(`periodos_${periodo}_indicador`)
            });

            const copiloto = document.getElementById('copilot');
            const copilotoBloque = document.getElementById('copilot-bloque');
            const copilotoEnfoque = document.getElementById('copilot-enfoque');

            const usaTemaLibre = periodo => {
                const contenedorTema = document.getElementById(`periodos_${periodo}_tema_libre`);
                return Boolean(contenedorTema && !contenedorTema.hidden);
            };

            const baseSeleccionIa = periodo => {
                if (document.getElementById(`periodos_${periodo}_tema`)?.value.trim()) return 'según tu tema';
                if (document.getElementById('copilot-indicaciones')?.value.trim()) return 'según tus indicaciones';
                return document.getElementById('titulo_unidad')?.value.trim() ? 'según la unidad y propondrá el tema' : 'y propondrá el tema';
            };

            const actualizarBotonIa = periodo => {
                if (!copiloto || String(periodo) !== copilotoBloque.value) return;
                const { destreza, indicador } = controles(periodo);
                const curriculoListo = Boolean(destreza?.value && indicador?.value);
                const eligeIa = !curriculoListo && usaTemaLibre(periodo);
                const listo = curriculoListo || eligeIa;
                const boton = copiloto.querySelector('.copilot-generate');
                if (!boton.classList.contains('is-loading')) {
                    boton.disabled = !listo || generacionIaActiva;
                }
                const enfoque = copilotoEnfoque.value;
                const entradaEnfocada = enfoque ? document.getElementById(`periodos_${periodo}_${enfoque}`) : null;
                const minutos = copilotoBloque.selectedOptions[0]?.dataset.duration;
                copiloto.querySelector('.copilot-readiness').classList.toggle('is-ready', listo);
                copiloto.querySelector('[data-copilot-ready-title]').textContent = curriculoListo
                    ? 'Contexto curricular listo'
                    : (eligeIa
                        ? `La IA elegirá la destreza y su indicador ${baseSeleccionIa(periodo)}`
                        : 'Elige la destreza y el indicador del bloque');
                copiloto.querySelector('[data-copilot-ready-detail]').textContent = !enfoque
                    ? `${minutos} minutos · se propondrán todos los campos del bloque`
                    : (entradaEnfocada?.value.trim()
                        ? `${minutos} minutos · mejorará el texto actual de este campo`
                        : `${minutos} minutos · redactará solo este campo`);
                const nombreEnfoque = copilotoEnfoque.selectedOptions[0]?.textContent.trim() || '';
                copiloto.querySelector('[data-copilot-label]').textContent = enfoque
                    ? `Preparar ${nombreEnfoque.startsWith('DUA') ? nombreEnfoque : nombreEnfoque.charAt(0).toLowerCase() + nombreEnfoque.slice(1)}`
                    : 'Preparar todo el bloque';
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
                    contenedor.classList.toggle('is-topic-locked', bloqueadoPorTema && Boolean(select.value));
                    boton.title = bloqueadoPorTema
                        ? 'Este elemento fue asignado automáticamente por el tema del libro.'
                        : '';

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
                indicador?.addEventListener('change', () => {
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

            const tallerDe = periodo => document.getElementById(`periodos_${periodo}_taller`);
            const casillasTaller = periodo => [...(tallerDe(periodo)?.querySelectorAll('input[type="checkbox"]') ?? [])];
            const textoOpcion = (select, valor) => [...select.options].find(opcion => opcion.value === String(valor))?.textContent.trim() || '';

            const fijarCurriculo = (periodo, destrezaId, indicadorId, bloquear) => {
                const { destreza, indicador } = controles(periodo);
                destreza.dataset.topicLocked = bloquear ? 'true' : 'false';
                indicador.dataset.topicLocked = bloquear ? 'true' : 'false';
                if (destreza.value !== String(destrezaId ?? '')) {
                    destreza.value = String(destrezaId ?? '');
                    destreza.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (indicadorId && indicador.value !== String(indicadorId)) {
                    indicador.value = String(indicadorId);
                    indicador.dispatchEvent(new Event('change', { bubbles: true }));
                }
                selectoresCatalogo.get(destreza)?.reconstruir();
                selectoresCatalogo.get(indicador)?.reconstruir();
            };

            const temasDeBloquesAnteriores = periodo => {
                const temas = new Map();
                document.querySelectorAll('.book-topic-select').forEach(selector => {
                    const otro = Number(selector.dataset.period);
                    if (otro >= Number(periodo)) return;
                    const ids = selector.value === 'taller'
                        ? casillasTaller(otro).filter(casilla => casilla.checked).map(casilla => casilla.value)
                        : (selector.value ? [selector.value] : []);
                    ids.forEach(id => {
                        const casilla = casillasTaller(periodo).find(candidata => candidata.value === String(id));
                        if (casilla) temas.set(String(id), casilla);
                    });
                });
                return [...temas.values()];
            };

            const renderizarOpcionesRapidas = periodo => {
                const taller = tallerDe(periodo);
                if (!taller || taller.hidden) return;
                const casillas = temasDeBloquesAnteriores(periodo);
                const contenedor = taller.querySelector('[data-workshop-quick]');
                contenedor.replaceChildren(...casillas.map(casilla => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'planning-workshop-chip';
                    chip.setAttribute('aria-pressed', casilla.checked ? 'true' : 'false');
                    chip.textContent = `${casilla.checked ? '✓ ' : '+ '}${casilla.dataset.etiqueta} · ${casilla.dataset.titulo}`;
                    chip.addEventListener('click', () => {
                        casilla.checked = !casilla.checked;
                        casilla.closest('details').open = true;
                        casilla.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    return chip;
                }));
                taller.querySelector('.planning-workshop-quick').hidden = casillas.length === 0;
            };

            const actualizarTaller = periodo => {
                const taller = tallerDe(periodo);
                if (!taller || taller.hidden) return;
                const marcadas = casillasTaller(periodo).filter(casilla => casilla.checked);
                taller.querySelector('[data-workshop-total]').textContent = marcadas.length === 1 ? '1 tema' : `${marcadas.length} temas`;
                taller.querySelectorAll('.planning-workshop-unit').forEach(unidad => {
                    const cantidad = unidad.querySelectorAll('input:checked').length;
                    unidad.querySelector('[data-workshop-count]').textContent = cantidad || '';
                });

                const campoTema = document.getElementById(`periodos_${periodo}_tema`);
                if (campoTema && campoTema.dataset.tituloManual !== 'true') {
                    campoTema.value = marcadas.length ? `Taller: ${marcadas.map(casilla => casilla.dataset.titulo).join('; ')}` : '';
                }

                const parejas = [];
                marcadas.forEach(casilla => {
                    const { destreza, indicador } = casilla.dataset;
                    if (!destreza || !indicador) return;
                    const existente = parejas.find(pareja => pareja.destreza === destreza && pareja.indicador === indicador);
                    if (existente) {
                        existente.temas.push(casilla.dataset.etiqueta);
                    } else {
                        parejas.push({ destreza, indicador, temas: [casilla.dataset.etiqueta] });
                    }
                });

                const { destreza, indicador } = controles(periodo);
                const choque = taller.querySelector('.planning-workshop-conflict');
                const opciones = choque.querySelector('[data-workshop-options]');
                choque.hidden = parejas.length < 2;
                opciones.replaceChildren();

                if (parejas.length === 0) {
                    destreza.dataset.topicLocked = 'false';
                    indicador.dataset.topicLocked = 'false';
                    selectoresCatalogo.get(destreza)?.reconstruir();
                    selectoresCatalogo.get(indicador)?.reconstruir();
                    return;
                }
                if (parejas.length === 1) {
                    fijarCurriculo(periodo, parejas[0].destreza, parejas[0].indicador, true);
                    return;
                }

                const actual = parejas.find(pareja => pareja.destreza === destreza.value && pareja.indicador === indicador.value);
                parejas.forEach((pareja, posicion) => {
                    const opcion = document.createElement('label');
                    opcion.className = 'planning-workshop-option';
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = `periodos_${periodo}_taller_pareja`;
                    radio.checked = pareja === actual;
                    const detalle = document.createElement('span');
                    const temas = document.createElement('small');
                    temas.textContent = pareja.temas.join(', ');
                    const textoDestreza = document.createElement('strong');
                    textoDestreza.textContent = textoOpcion(destreza, pareja.destreza) || `Destreza ${posicion + 1}`;
                    const indicadorRelacionado = (relacionados[pareja.destreza] || []).find(item => String(item.id) === pareja.indicador);
                    const textoIndicador = document.createElement('em');
                    textoIndicador.textContent = indicadorRelacionado ? descripcionSinCodigo(indicadorRelacionado) : '';
                    detalle.append(temas, textoDestreza, textoIndicador);
                    radio.addEventListener('change', () => fijarCurriculo(periodo, pareja.destreza, pareja.indicador, true));
                    opcion.append(radio, detalle);
                    opciones.append(opcion);
                });
                if (!actual) fijarCurriculo(periodo, '', null, true);
            };

            const activarTaller = (periodo, activo) => {
                const taller = tallerDe(periodo);
                if (!taller) return;
                const campoTema = document.getElementById(`periodos_${periodo}_tema`);
                const etiquetaTema = document.querySelector(`label[for="periodos_${periodo}_tema"]`);
                const estabaActivo = !taller.hidden;
                taller.hidden = !activo;
                casillasTaller(periodo).forEach(casilla => casilla.disabled = !activo);
                if (etiquetaTema?.firstChild) etiquetaTema.firstChild.textContent = activo ? 'Título del taller' : 'Escribe tu tema';
                if (activo) {
                    if (campoTema && !estabaActivo) {
                        campoTema.dataset.tituloManual = campoTema.value.trim() && !campoTema.value.startsWith('Taller:') ? 'true' : 'false';
                    }
                    renderizarOpcionesRapidas(periodo);
                    actualizarTaller(periodo);
                } else if (campoTema) {
                    delete campoTema.dataset.tituloManual;
                }
            };

            document.querySelectorAll('.planning-workshop').forEach(taller => {
                const periodo = taller.dataset.period;
                taller.addEventListener('change', evento => {
                    if (evento.target.matches('input[type="checkbox"]')) {
                        actualizarTaller(periodo);
                        renderizarOpcionesRapidas(periodo);
                        document.querySelectorAll('.planning-workshop').forEach(otro => renderizarOpcionesRapidas(otro.dataset.period));
                    }
                });
                document.getElementById(`periodos_${periodo}_tema`)?.addEventListener('input', evento => {
                    if (!taller.hidden && evento.isTrusted) evento.target.dataset.tituloManual = evento.target.value.trim() ? 'true' : 'false';
                });
            });

            const aplicarTemaLibro = (selector, limpiarTemaLibre = false) => {
                const periodo = selector.dataset.period;
                const unidad = unidadActual();
                const esTaller = selector.value === 'taller';
                const temaLibro = esTaller ? null : unidad?.temas.find(tema => String(tema.id) === selector.value && tema.disponible);
                const { destreza, indicador } = controles(periodo);
                const campoTema = document.getElementById(`periodos_${periodo}_tema`);
                const temaLibre = document.getElementById(`periodos_${periodo}_tema_libre`);

                destreza.dataset.topicLocked = temaLibro ? 'true' : 'false';
                indicador.dataset.topicLocked = temaLibro ? 'true' : 'false';
                if (temaLibre) temaLibre.hidden = Boolean(temaLibro);
                selector.title = selector.selectedOptions[0]?.textContent.trim() || '';
                activarTaller(periodo, esTaller);
                if (esTaller) return;

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
                    const conservaTaller = selector.value === 'taller' || selector.dataset.selected === 'taller';
                    const seleccionAnterior = limpiarSeleccion && !conservaTaller ? '' : selector.dataset.selected || selector.value;
                    selector.replaceChildren(new Option('Tema libre', ''), new Option('Taller · varios temas del libro', 'taller'));
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
                    if (!selector.value) document.getElementById(`periodos_${selector.dataset.period}_tema`)?.focus();
                    document.querySelectorAll('.planning-workshop').forEach(taller => renderizarOpcionesRapidas(taller.dataset.period));
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
                if (evento.key !== 'Escape') return;
                selectoresCatalogo.forEach(selector => selector.cerrar());
                if (copiloto && !copiloto.hidden && copiloto.contains(document.activeElement)) cerrarCopiloto();
            });

            if (!copiloto) return;

            const etiquetasCampos = {
                tema: 'Tema',
                recursos: 'Recursos',
                experiencia: 'Experiencia',
                dua_experiencia: 'DUA de la experiencia',
                reflexion: 'Reflexión',
                dua_reflexion: 'DUA de la reflexión',
                conceptualizacion: 'Conceptualización',
                dua_conceptualizacion: 'DUA de la conceptualización',
                aplicacion: 'Aplicación',
                dua_aplicacion: 'DUA de la aplicación',
                tecnica_evaluacion: 'Técnica de evaluación',
                instrumento_evaluacion: 'Instrumento de evaluación',
                seleccion: 'Destreza e indicador'
            };
            const camposEnfocables = camposGenerados.filter(campo => campo !== 'tema');
            const estadoCopiloto = copiloto.querySelector('.copilot-status');
            const listaCambios = copiloto.querySelector('.copilot-changes');
            const cambiosPendientes = new Map();
            let aplicandoCambio = false;
            let activadorCopiloto = null;

            const numeroBloque = periodo => String(Number(periodo) + 1).padStart(2, '0');

            const mostrarEstado = (mensaje, tipo = '') => {
                estadoCopiloto.hidden = !mensaje;
                estadoCopiloto.className = `copilot-status${tipo ? ` is-${tipo}` : ''}`;
                estadoCopiloto.textContent = mensaje;
            };

            const irABloque = periodo => {
                document.getElementById(`bloque-${periodo}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            let sincronizandoSelect = false;

            /**
             * Cambia un select por código y avisa con «change» para que el selector visual
             * (public/js/selects.js) se redibuje, sin repetir los efectos de los oyentes del copiloto.
             */
            const sincronizarSelect = (select, valor) => {
                if (select.value === valor) return;
                select.value = valor;
                sincronizandoSelect = true;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                sincronizandoSelect = false;
            };

            const abrirCopiloto = (periodo, enfoque = '', activador = null) => {
                sincronizarSelect(copilotoBloque, String(periodo));
                sincronizarSelect(copilotoEnfoque, enfoque);
                activadorCopiloto = activador;
                copiloto.hidden = false;
                document.body.classList.add('copilot-open');
                actualizarBotonIa(periodo);
                if (enfoque) {
                    document.getElementById(`periodos_${periodo}_${enfoque}`)?.closest('.field')
                        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    irABloque(periodo);
                }
                copiloto.querySelector('#copilot-indicaciones').focus({ preventScroll: true });
            };

            const cerrarCopiloto = () => {
                copiloto.hidden = true;
                document.body.classList.remove('copilot-open');
                activadorCopiloto?.focus({ preventScroll: true });
                activadorCopiloto = null;
            };

            document.querySelectorAll('[data-ai-open]').forEach(activador => {
                activador.addEventListener('click', () => abrirCopiloto(activador.dataset.aiOpen, '', activador));
            });
            copiloto.querySelector('.copilot-close').addEventListener('click', cerrarCopiloto);
            copilotoBloque.addEventListener('change', () => {
                if (sincronizandoSelect) return;
                actualizarBotonIa(copilotoBloque.value);
                irABloque(copilotoBloque.value);
            });
            copilotoEnfoque.addEventListener('change', () => {
                if (sincronizandoSelect) return;
                actualizarBotonIa(copilotoBloque.value);
                const enfoque = copilotoEnfoque.value;
                if (enfoque) {
                    document.getElementById(`periodos_${copilotoBloque.value}_${enfoque}`)?.closest('.field')
                        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            copiloto.querySelector('#copilot-indicaciones').addEventListener('input', () => actualizarBotonIa(copilotoBloque.value));

            document.querySelectorAll('.planning-period-card').forEach(tarjeta => {
                const periodo = tarjeta.dataset.period;
                camposEnfocables.forEach(campo => {
                    const entrada = document.getElementById(`periodos_${periodo}_${campo}`);
                    const etiqueta = entrada ? tarjeta.querySelector(`label[for="${entrada.id}"]`) : null;
                    if (!etiqueta) return;
                    const disparador = document.createElement('button');
                    disparador.type = 'button';
                    disparador.className = 'copilot-field-trigger';
                    disparador.textContent = '✦';
                    disparador.title = `Preparar «${etiquetasCampos[campo]}» con el copiloto`;
                    disparador.setAttribute('aria-label', disparador.title);
                    disparador.addEventListener('click', evento => {
                        evento.preventDefault();
                        abrirCopiloto(periodo, campo, disparador);
                    });
                    etiqueta.append(disparador);
                });
                const tema = document.getElementById(`periodos_${periodo}_tema`);
                tema?.addEventListener('input', () => {
                    if (!aplicandoCambio && String(periodo) === copilotoBloque.value) actualizarBotonIa(periodo);
                });
            });

            const renderizarCambios = () => {
                const lista = listaCambios.querySelector('ul');
                lista.replaceChildren();
                cambiosPendientes.forEach((cambio, id) => {
                    const elemento = document.createElement('li');
                    const ir = document.createElement('button');
                    ir.type = 'button';
                    ir.className = 'copilot-change-go';
                    const bloque = document.createElement('small');
                    bloque.textContent = `Bloque ${numeroBloque(cambio.periodo)} · `;
                    ir.append(bloque, etiquetasCampos[cambio.campo]);
                    ir.addEventListener('click', () => cambio.irA());
                    const descartar = document.createElement('button');
                    descartar.type = 'button';
                    descartar.className = 'copilot-icon-button is-discard';
                    descartar.textContent = '✕';
                    descartar.title = `Descartar cambio en ${etiquetasCampos[cambio.campo]}`;
                    descartar.setAttribute('aria-label', descartar.title);
                    descartar.addEventListener('click', () => resolverCambio(id, false));
                    const mantener = document.createElement('button');
                    mantener.type = 'button';
                    mantener.className = 'copilot-icon-button is-keep';
                    mantener.textContent = '✓';
                    mantener.title = `Mantener cambio en ${etiquetasCampos[cambio.campo]}`;
                    mantener.setAttribute('aria-label', mantener.title);
                    mantener.addEventListener('click', () => resolverCambio(id, true));
                    elemento.append(ir, descartar, mantener);
                    lista.append(elemento);
                });
                listaCambios.hidden = cambiosPendientes.size === 0;
                listaCambios.querySelector('[data-copilot-count]').textContent = cambiosPendientes.size === 1
                    ? '1 pendiente'
                    : `${cambiosPendientes.size} pendientes`;
            };

            const escribirEnCampo = (entrada, valor) => {
                aplicandoCambio = true;
                entrada.value = valor;
                entrada.dispatchEvent(new Event('input', { bubbles: true }));
                aplicandoCambio = false;
            };

            function resolverCambio(id, mantener, silencioso = false) {
                const cambio = cambiosPendientes.get(id);
                if (!cambio) return;
                if (!mantener) cambio.restaurar();
                cambio.limpiar();
                cambiosPendientes.delete(id);
                renderizarCambios();
                if (!silencioso && cambiosPendientes.size === 0) {
                    mostrarEstado(mantener ? 'Cambios conservados. Revisa el texto antes de guardar el borrador.' : 'Cambio descartado. El campo volvió a su texto anterior.');
                }
            }

            const proponerCambio = (periodo, campo, valor) => {
                const entrada = document.getElementById(`periodos_${periodo}_${campo}`);
                const contenedor = entrada?.closest('.field');
                if (!entrada || !contenedor || typeof valor !== 'string' || valor.trim() === entrada.value.trim()) return false;

                const previo = cambiosPendientes.get(entrada.id);
                const original = previo ? previo.original : entrada.value;
                if (previo) resolverCambio(entrada.id, true, true);

                const barra = document.createElement('div');
                barra.className = 'copilot-inline';
                const aviso = document.createElement('span');
                aviso.textContent = '✦ Propuesta';
                const verAnterior = document.createElement('button');
                verAnterior.type = 'button';
                verAnterior.className = 'copilot-previous-toggle';
                verAnterior.textContent = 'Ver anterior';
                verAnterior.setAttribute('aria-expanded', 'false');
                const descartar = document.createElement('button');
                descartar.type = 'button';
                descartar.className = 'copilot-discard';
                descartar.textContent = 'Descartar';
                const mantener = document.createElement('button');
                mantener.type = 'button';
                mantener.className = 'copilot-keep';
                mantener.textContent = 'Mantener';
                barra.append(aviso, verAnterior, descartar, mantener);

                const anterior = document.createElement('div');
                anterior.className = `copilot-previous${original.trim() ? '' : ' is-empty'}`;
                anterior.textContent = original.trim() || 'El campo estaba vacío.';
                anterior.hidden = true;

                const etiqueta = contenedor.querySelector('label');
                (etiqueta ?? entrada).after(barra);
                barra.after(anterior);
                contenedor.classList.add('copilot-pending');
                escribirEnCampo(entrada, valor);

                const alEditar = () => {
                    if (!aplicandoCambio) resolverCambio(entrada.id, true);
                };
                verAnterior.addEventListener('click', () => {
                    anterior.hidden = !anterior.hidden;
                    verAnterior.textContent = anterior.hidden ? 'Ver anterior' : 'Ocultar anterior';
                    verAnterior.setAttribute('aria-expanded', anterior.hidden ? 'false' : 'true');
                });
                descartar.addEventListener('click', () => resolverCambio(entrada.id, false));
                mantener.addEventListener('click', () => resolverCambio(entrada.id, true));
                entrada.addEventListener('input', alEditar);
                cambiosPendientes.set(entrada.id, {
                    periodo,
                    campo,
                    original,
                    restaurar: () => escribirEnCampo(entrada, original),
                    limpiar: () => {
                        barra.remove();
                        anterior.remove();
                        contenedor.classList.remove('copilot-pending');
                        entrada.removeEventListener('input', alEditar);
                    },
                    irA: () => {
                        contenedor.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        entrada.focus({ preventScroll: true });
                    }
                });

                return true;
            };

            const elegirEnCatalogo = (select, valor) => {
                aplicandoCambio = true;
                select.value = String(valor);
                select.dispatchEvent(new Event('change', { bubbles: true }));
                aplicandoCambio = false;
            };

            const proponerSeleccion = (periodo, seleccion) => {
                const { destreza, indicador } = controles(periodo);
                if (!destreza || !indicador || !seleccion?.destreza_curricular_id) return false;
                const id = `periodos_${periodo}_seleccion`;
                const campos = [destreza, indicador].map(select => select.closest('.field'));
                const original = { destreza: destreza.value, indicador: indicador.value };
                resolverCambio(id, true, true);

                elegirEnCatalogo(destreza, seleccion.destreza_curricular_id);
                elegirEnCatalogo(indicador, seleccion.indicador_evaluacion_id);
                if (destreza.value !== String(seleccion.destreza_curricular_id) || indicador.value !== String(seleccion.indicador_evaluacion_id)) {
                    elegirEnCatalogo(destreza, original.destreza);
                    elegirEnCatalogo(indicador, original.indicador);
                    return false;
                }

                const barra = document.createElement('div');
                barra.className = 'copilot-inline planning-period-full';
                const aviso = document.createElement('span');
                aviso.textContent = '✦ Destreza e indicador elegidos por el copiloto';
                const descartar = document.createElement('button');
                descartar.type = 'button';
                descartar.className = 'copilot-discard';
                descartar.textContent = 'Descartar';
                const mantener = document.createElement('button');
                mantener.type = 'button';
                mantener.className = 'copilot-keep';
                mantener.textContent = 'Mantener';
                barra.append(aviso, descartar, mantener);
                campos[0].before(barra);
                campos.forEach(campo => campo.classList.add('copilot-pending'));

                const alCambiar = () => {
                    if (!aplicandoCambio) resolverCambio(id, true);
                };
                destreza.addEventListener('change', alCambiar);
                indicador.addEventListener('change', alCambiar);
                descartar.addEventListener('click', () => resolverCambio(id, false));
                mantener.addEventListener('click', () => resolverCambio(id, true));
                cambiosPendientes.set(id, {
                    periodo,
                    campo: 'seleccion',
                    restaurar: () => {
                        elegirEnCatalogo(destreza, original.destreza);
                        elegirEnCatalogo(indicador, original.indicador);
                    },
                    limpiar: () => {
                        barra.remove();
                        campos.forEach(campo => campo.classList.remove('copilot-pending'));
                        destreza.removeEventListener('change', alCambiar);
                        indicador.removeEventListener('change', alCambiar);
                    },
                    irA: () => {
                        barra.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        mantener.focus({ preventScroll: true });
                    }
                });

                return true;
            };

            copiloto.querySelector('.copilot-keep-all').addEventListener('click', () => {
                [...cambiosPendientes.keys()].forEach(id => resolverCambio(id, true, true));
                mostrarEstado('Se conservaron todos los cambios. Revisa el texto antes de guardar el borrador.', 'success');
            });
            copiloto.querySelector('.copilot-discard-all').addEventListener('click', () => {
                [...cambiosPendientes.keys()].forEach(id => resolverCambio(id, false, true));
                mostrarEstado('Se descartaron todos los cambios. Los campos volvieron a su texto anterior.');
            });

            const botonGenerar = copiloto.querySelector('.copilot-generate');
            const contador = copiloto.querySelector('.copilot-timer');
            const formatearTiempo = milisegundos => {
                const segundosTotales = Math.floor(milisegundos / 1000);
                return `${String(Math.floor(segundosTotales / 60)).padStart(2, '0')}:${String(segundosTotales % 60).padStart(2, '0')}`;
            };

            botonGenerar.addEventListener('click', async () => {
                if (generacionIaActiva) return;
                const periodo = copilotoBloque.value;
                const enfoque = copilotoEnfoque.value;
                const { destreza, indicador } = controles(periodo);
                const curriculoListo = Boolean(destreza?.value && indicador?.value);
                if (!curriculoListo && !usaTemaLibre(periodo)) return;

                generacionIaActiva = true;
                const inicio = Date.now();
                const intervalo = window.setInterval(() => contador.textContent = formatearTiempo(Date.now() - inicio), 250);
                const etiqueta = botonGenerar.querySelector('[data-copilot-label]');
                botonGenerar.classList.add('is-loading');
                botonGenerar.disabled = true;
                copilotoBloque.disabled = true;
                copilotoEnfoque.disabled = true;
                etiqueta.textContent = 'Preparando…';
                contador.textContent = '00:00';
                contador.hidden = false;
                mostrarEstado((curriculoListo ? '' : 'Eligiendo la destreza y el indicador según tu tema. ') + (enfoque
                    ? `Redactando «${etiquetasCampos[enfoque]}» con el contexto del bloque ${numeroBloque(periodo)}…`
                    : `Organizando las actividades ERCA y sus apoyos DUA del bloque ${numeroBloque(periodo)}. Puede tardar un momento.`));

                try {
                    const contenidoActual = Object.fromEntries(camposGenerados.map(campo => [
                        campo, document.getElementById(`periodos_${periodo}_${campo}`)?.value.trim() || null
                    ]));
                    const indicaciones = copiloto.querySelector('#copilot-indicaciones').value.trim();
                    const solicitud = {
                        asignacion_id: asignacionId,
                        destreza_curricular_id: curriculoListo ? Number(destreza.value) : null,
                        indicador_evaluacion_id: curriculoListo ? Number(indicador.value) : null,
                        duracion_minutos: Number(copilotoBloque.selectedOptions[0].dataset.duration),
                        titulo_unidad: document.getElementById('titulo_unidad')?.value || null,
                        tema: contenidoActual.tema,
                        indicaciones: indicaciones || null,
                        enfoque: enfoque || null,
                        contenido_actual: contenidoActual
                    };
                    const enviar = () => fetch(botonGenerar.dataset.endpoint, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        credentials: 'same-origin',
                        body: JSON.stringify(solicitud)
                    });

                    let respuesta = await enviar();
                    if (respuesta.status === 419 && await actualizarCsrf()) respuesta = await enviar();
                    if (respuesta.status === 419) {
                        throw new Error('Tu sesión expiró. Copia cualquier texto que quieras conservar y recarga la página para iniciar sesión nuevamente.');
                    }
                    const datos = await respuesta.json().catch(() => ({}));
                    if (!respuesta.ok) {
                        throw new Error(Object.values(datos.errors || {}).flat()[0] || datos.message || 'No se pudo generar la propuesta.');
                    }

                    const temaLibreVisible = !document.getElementById(`periodos_${periodo}_tema_libre`)?.hidden;
                    const seleccionAplicada = datos.seleccion ? proponerSeleccion(periodo, datos.seleccion) : false;
                    const camposPropuestos = Object.keys(datos.propuesta || {})
                        .filter(campo => campo !== 'tema' || (temaLibreVisible && !contenidoActual.tema))
                        .filter(campo => proponerCambio(periodo, campo, datos.propuesta[campo]));
                    renderizarCambios();
                    const totalCambios = camposPropuestos.length + (seleccionAplicada ? 1 : 0);
                    if (seleccionAplicada) {
                        cambiosPendientes.get(`periodos_${periodo}_seleccion`)?.irA();
                    } else if (camposPropuestos[0]) {
                        document.getElementById(`periodos_${periodo}_${camposPropuestos[0]}`)?.closest('.field')
                            .scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    mostrarEstado(totalCambios
                        ? `${totalCambios === 1 ? 'Un cambio propuesto' : `${totalCambios} cambios propuestos`} en ${formatearTiempo(Date.now() - inicio)}${seleccionAplicada ? ', incluida la destreza y su indicador' : ''}. Revisa cada uno y elige Mantener o Descartar.`
                        : 'La propuesta coincide con lo que ya tienes escrito; no hubo cambios.', 'success');
                } catch (error) {
                    mostrarEstado(error instanceof TypeError && /fetch/i.test(error.message)
                        ? 'La conexión con el servidor se interrumpió mientras Gemini generaba la propuesta. Vuelve a intentarlo.'
                        : (error.message || 'No se pudo generar la propuesta.'), 'error');
                } finally {
                    window.clearInterval(intervalo);
                    botonGenerar.classList.remove('is-loading');
                    copilotoBloque.disabled = false;
                    copilotoEnfoque.disabled = false;
                    contador.hidden = true;
                    generacionIaActiva = false;
                    actualizarBotonIa(copilotoBloque.value);
                }
            });

            document.querySelector('.planning-form')?.addEventListener('submit', () => {
                [...cambiosPendientes.keys()].forEach(id => resolverCambio(id, true, true));
            });
        })();
    </script>
</x-layouts.aplicacion>
