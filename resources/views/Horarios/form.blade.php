<x-layouts.aplicacion :title="$horario->exists ? 'Editar horario' : 'Nuevo horario'">
    @php($bloquesFormulario = old('bloques', $horario->exists ? $horario->bloques->map(fn($bloque) => ['materia_id' => $bloque->materia_id, 'dia_semana' => $bloque->dia_semana, 'hora_inicio' => substr($bloque->hora_inicio, 0, 5), 'hora_fin' => substr($bloque->hora_fin, 0, 5)])->all() : [['materia_id' => '', 'dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '07:40']]))
    <div class="page-heading">
        <div>
            <p class="eyebrow">HORARIO SEMANAL</p>
            <h1>{{ $horario->exists ? 'Editar horario' : 'Crear horario' }}</h1>
            <p>El docente seleccionado será responsable de todas las materias incluidas.</p>
        </div><a class="secondary-button"
            href="{{ $horario->exists ? route('horarios.show', $horario) : route('horarios.index') }}">Volver</a>
    </div>
    @if ($errors->any())
        <div class="delete-confirmation" role="alert"><strong>Revisa la información.</strong>
            <ul>
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ $horario->exists ? route('horarios.update', $horario) : route('horarios.store') }}"
        class="schedule-form" data-schedule-form>
        @csrf @if ($horario->exists)
            @method('PUT')<input type="hidden" name="version" value="{{ $horario->version }}">
        @endif
        <section class="panel">
            <div class="planning-section-heading"><span>01</span>
                <div>
                    <h2>Responsable del curso</h2>
                    <p>Durante esta etapa, cada docente atiende un solo curso por período.</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="field"><label for="docente_id">Docente *</label><select id="docente_id" name="docente_id"
                        required>
                        <option value="">Selecciona un docente</option>
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}" @selected(old('docente_id', $horario->docente_id) == $docente->id)>{{ $docente->apellidos }}
                                {{ $docente->nombres }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="seccion_id">Curso / paralelo / período *</label><select id="seccion_id"
                        name="seccion_id" required>
                        <option value="">Selecciona un curso</option>
                        @foreach ($secciones as $seccion)
                            @php($recreoSeccion = $recreos->get($seccion->periodo_academico_id . '-' . $seccion->curso->subnivel))
                            <option value="{{ $seccion->id }}"
                                data-recreo="{{ $recreoSeccion ? $recreoSeccion->nombre . ' · ' . substr($recreoSeccion->hora_inicio, 0, 5) . '–' . substr($recreoSeccion->hora_fin, 0, 5) : '' }}"
                                @selected(old('seccion_id', $horario->seccion_id) == $seccion->id)>{{ $seccion->etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="schedule-recess-note" data-recess-note hidden></p>
        </section>
        <section class="panel">
            <div class="schedule-blocks-heading">
                <div class="planning-section-heading"><span>02</span>
                    <div>
                        <h2>Bloques de clases</h2>
                        <p>Agrega cada materia con su día y rango de horas.</p>
                    </div>
                </div><button class="secondary-button" type="button" data-add-block>+ Agregar bloque</button>
            </div>
            <div class="schedule-blocks" data-blocks>
                @foreach ($bloquesFormulario as $indice => $bloque)
                    <div class="schedule-block" data-block>
                        <div class="field schedule-subject"><label>Materia *</label><select
                                name="bloques[{{ $indice }}][materia_id]" required>
                                <option value="">Selecciona</option>
                                @foreach ($materias as $materia)
                                    <option value="{{ $materia->id }}" @selected(($bloque['materia_id'] ?? '') == $materia->id)>
                                        {{ $materia->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Día *</label><select name="bloques[{{ $indice }}][dia_semana]"
                                required>
                                @foreach (App\Models\Horario::DIAS as $numero => $dia)
                                    <option value="{{ $numero }}" @selected(($bloque['dia_semana'] ?? 1) == $numero)>
                                        {{ $dia }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Inicio *</label><input
                                name="bloques[{{ $indice }}][hora_inicio]" type="time"
                                value="{{ $bloque['hora_inicio'] ?? '07:00' }}" required></div>
                        <div class="field"><label>Fin *</label><input name="bloques[{{ $indice }}][hora_fin]"
                                type="time" value="{{ $bloque['hora_fin'] ?? '07:40' }}" required></div><button
                            class="schedule-remove" type="button" data-remove-block
                            aria-label="Quitar bloque">×</button>
                    </div>
                @endforeach
            </div>
        </section>
        <div class="form-actions"><a class="secondary-button" href="{{ route('horarios.index') }}">Cancelar</a><button
                class="primary-button" type="submit">Guardar horario</button></div>
    </form>
    <template data-block-template>
        <div class="schedule-block" data-block>
            <div class="field schedule-subject"><label>Materia *</label><select data-name="materia_id" required>
                    <option value="">Selecciona</option>
                    @foreach ($materias as $materia)
                        <option value="{{ $materia->id }}">{{ $materia->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Día *</label><select data-name="dia_semana" required>
                    @foreach (App\Models\Horario::DIAS as $numero => $dia)
                        <option value="{{ $numero }}">{{ $dia }}</option>
                    @endforeach
                </select></div>
            <div class="field"><label>Inicio *</label><input data-name="hora_inicio" type="time" value="07:00"
                    required></div>
            <div class="field"><label>Fin *</label><input data-name="hora_fin" type="time" value="07:40" required>
            </div><button class="schedule-remove" type="button" data-remove-block aria-label="Quitar bloque">×</button>
        </div>
    </template>
    <script src="{{ asset('js/horarios.js') }}?v={{ filemtime(public_path('js/horarios.js')) }}" defer></script>
</x-layouts.aplicacion>
