<x-layouts.aplicacion title="Cronograma lectivo">
    <div class="page-heading">
        <div><p class="eyebrow">ORGANIZACIÓN DEL AÑO LECTIVO</p><h1>Cronograma lectivo</h1><p>Consulta los trimestres y los días en los que no habrá clases.</p></div>
        <form method="GET" action="{{ route('cronogramas.index') }}" class="field calendar-period-filter"><label for="periodo_id">Período académico</label><select id="periodo_id" name="periodo_id" onchange="this.form.submit()">@foreach($periodos as $opcion)<option value="{{ $opcion->id }}" @selected($periodo?->id === $opcion->id)>{{ $opcion->nombre }}</option>@endforeach</select></form>
    </div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><strong>Revisa el cronograma.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($periodo)
        @php($cierres = $periodo->cierresTrimestrales->keyBy('numero'))
        <section class="calendar-overview">
            <article><span>Inicio del año lectivo</span><strong>{{ $periodo->fecha_inicio->translatedFormat('d M Y') }}</strong></article>
            <article><span>Finalización</span><strong>{{ $periodo->fecha_fin->translatedFormat('d M Y') }}</strong></article>
            <article><span>Semanas del período</span><strong>{{ $calendario['semanas']->count() }}</strong></article>
            <article><span>Días lectivos</span><strong>{{ $calendario['total_dias_lectivos'] }}</strong></article>
            <article><span>Días sin clases</span><strong>{{ $calendario['total_dias_sin_clases'] }}</strong></article>
        </section>

        @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
            <section class="panel calendar-settings">
                <div class="panel-heading"><div><h2>Configuración del año lectivo</h2><p>Los tres trimestres deben finalizar en orden; el tercero coincide con el cierre del año.</p></div></div>
                <form method="POST" action="{{ route('cronogramas.update', $periodo) }}">@csrf @method('PUT')
                    <div class="calendar-date-grid">
                        <div class="field"><label for="fecha_inicio">Inicio *</label><input class="calendar-input" data-calendar id="fecha_inicio" name="fecha_inicio" type="date" placeholder="dd/mm/aaaa" value="{{ old('fecha_inicio', $periodo->fecha_inicio->format('Y-m-d')) }}" required></div>
                        <div class="field"><label for="trimestre_1_fin">Fin del 1.er trimestre *</label><input class="calendar-input" data-calendar id="trimestre_1_fin" name="trimestre_1_fin" type="date" placeholder="dd/mm/aaaa" value="{{ old('trimestre_1_fin', $cierres->get(1)?->fecha_fin?->format('Y-m-d')) }}" required></div>
                        <div class="field"><label for="trimestre_2_fin">Fin del 2.º trimestre *</label><input class="calendar-input" data-calendar id="trimestre_2_fin" name="trimestre_2_fin" type="date" placeholder="dd/mm/aaaa" value="{{ old('trimestre_2_fin', $cierres->get(2)?->fecha_fin?->format('Y-m-d')) }}" required></div>
                        <div class="field"><label for="trimestre_3_fin">Fin del 3.er trimestre *</label><input class="calendar-input" data-calendar id="trimestre_3_fin" name="trimestre_3_fin" type="date" placeholder="dd/mm/aaaa" value="{{ old('trimestre_3_fin', $cierres->get(3)?->fecha_fin?->format('Y-m-d') ?? $periodo->fecha_fin->format('Y-m-d')) }}" required></div>
                        <div class="field"><label for="fecha_fin">Fin del año lectivo *</label><input class="calendar-input" data-calendar id="fecha_fin" name="fecha_fin" type="date" placeholder="dd/mm/aaaa" value="{{ old('fecha_fin', $periodo->fecha_fin->format('Y-m-d')) }}" required></div>
                    </div>
                    <div class="form-actions"><button class="primary-button" type="submit">Guardar cronograma</button></div>
                </form>
            </section>
        @endif

        <section class="calendar-terms">
            @foreach([1 => 'Primer trimestre', 2 => 'Segundo trimestre', 3 => 'Tercer trimestre'] as $numero => $nombre)
                @php($inicio = $numero === 1 ? $periodo->fecha_inicio : $cierres->get($numero - 1)?->fecha_fin?->copy()->addDay())
                <article class="panel"><span>0{{ $numero }}</span><h2>{{ $nombre }}</h2>@if($cierres->get($numero))<p>{{ $inicio?->format('d/m/Y') }} – {{ $cierres->get($numero)->fecha_fin->format('d/m/Y') }}</p>@else<p>Pendiente de configurar</p>@endif</article>
            @endforeach
        </section>

        <section class="panel calendar-weeks">
            <div class="panel-heading calendar-weeks-heading">
                <div><h2>Semanas del año lectivo</h2><p>Consulta los días de lunes a viernes. Las suspensiones aparecen marcadas y no generan planificaciones pendientes.</p></div>
                <div class="calendar-legend"><span><i class="is-school-day"></i>Día lectivo</span><span><i class="is-suspended-day"></i>Sin clases</span></div>
            </div>
            <div class="calendar-term-weeks">
                @foreach($calendario['semanas']->groupBy('trimestre') as $trimestre => $semanas)
                    <details class="calendar-term-group" @if((int) $trimestre === 1) open @endif>
                        <summary><span>Trimestre {{ $trimestre }}</span><small>{{ $semanas->count() }} {{ $semanas->count() === 1 ? 'semana' : 'semanas' }}</small></summary>
                        <div class="calendar-week-list">
                            @foreach($semanas as $semana)
                                <article class="calendar-week-row">
                                    <div class="calendar-week-meta">
                                        <strong>Semana {{ str_pad((string) $semana['numero'], 2, '0', STR_PAD_LEFT) }}</strong>
                                        <span>{{ \Carbon\Carbon::parse($semana['inicio'])->format('d/m') }} – {{ \Carbon\Carbon::parse($semana['fin'])->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="calendar-week-days">
                                        @foreach($semana['dias'] as $diaSemana)
                                            <div class="calendar-week-day {{ $diaSemana['es_lectivo'] ? 'is-school-day' : 'is-suspended-day' }}" @if($diaSemana['motivo']) title="{{ $diaSemana['motivo'] }}" @endif>
                                                <span>{{ $diaSemana['dia'] }}</span><strong>{{ $diaSemana['numero'] }}</strong><small>{{ $diaSemana['mes'] }}</small>
                                                <em>{{ $diaSemana['es_lectivo'] ? 'Clases' : 'Sin clases' }}</em>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="calendar-week-count"><strong>{{ $semana['dias_lectivos'] }}</strong><span>{{ $semana['dias_lectivos'] === 1 ? 'día lectivo' : 'días lectivos' }}</span></div>
                                </article>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="panel calendar-days">
            <div class="panel-heading"><div><h2>Días sin clases</h2><p>Estos días se excluyen automáticamente de los bloques que debe llenar el docente.</p></div></div>
            @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
                @php($tipoSuspension = old('tipo_suspension', old('fecha_fin') ? 'rango' : 'dia'))
                <form method="POST" action="{{ route('cronogramas.dias.store', $periodo) }}" class="calendar-day-form {{ $tipoSuspension === 'dia' ? 'is-single-day' : '' }}" data-suspension-form>
                    @csrf
                    <div class="calendar-day-form-heading">
                        <span class="calendar-day-form-icon"><svg aria-hidden="true"><use href="#i-calendar"/></svg></span>
                        <div><h3>Registrar suspensión de clases</h3><p>Selecciona si corresponde a un día específico o a varios días consecutivos.</p></div>
                    </div>
                    <div class="calendar-day-form-grid">
                        <div class="field">
                            <label for="tipo_suspension">Tipo de registro *</label>
                            <select id="tipo_suspension" name="tipo_suspension" required data-suspension-type>
                                <option value="dia" @selected($tipoSuspension === 'dia')>Un solo día</option>
                                <option value="rango" @selected($tipoSuspension === 'rango')>Rango de fechas</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="suspension_fecha" data-start-date-label>{{ $tipoSuspension === 'rango' ? 'Fecha inicial *' : 'Fecha *' }}</label>
                            <input class="calendar-input" data-calendar id="suspension_fecha" name="fecha" type="date" placeholder="dd/mm/aaaa" min="{{ $periodo->fecha_inicio->format('Y-m-d') }}" max="{{ $periodo->fecha_fin->format('Y-m-d') }}" value="{{ old('fecha') }}" required>
                        </div>
                        <div class="field" data-end-date-field @if($tipoSuspension !== 'rango') hidden @endif>
                            <label for="suspension_fecha_fin">Fecha final *</label>
                            <input class="calendar-input" data-calendar id="suspension_fecha_fin" name="fecha_fin" type="date" placeholder="dd/mm/aaaa" min="{{ $periodo->fecha_inicio->format('Y-m-d') }}" max="{{ $periodo->fecha_fin->format('Y-m-d') }}" value="{{ old('fecha_fin') }}" @required($tipoSuspension === 'rango')>
                        </div>
                        <div class="field calendar-day-reason">
                            <label for="motivo">Motivo *</label>
                            <input id="motivo" name="motivo" maxlength="500" value="{{ old('motivo') }}" placeholder="Ej. Vacaciones de Navidad y fin de año" required>
                        </div>
                    </div>
                    <div class="calendar-day-form-actions"><p>Las fechas registradas dejarán de aparecer en las planificaciones pendientes.</p><button class="primary-button" type="submit">Registrar suspensión</button></div>
                </form>
            @endif
            <div class="calendar-day-list">
                @forelse($periodo->diasNoClase as $dia)
                    @php($esPeriodo = $dia->fecha_fin && ! $dia->fecha_fin->isSameDay($dia->fecha))
                    <article>
                        <div class="calendar-day-date"><strong>{{ $dia->fecha->format('d') }}</strong><span>{{ mb_strtoupper($dia->fecha->locale('es')->translatedFormat('M')) }}</span></div>
                        <div>
                            <h3>
                                @if($esPeriodo)
                                    {{ ucfirst($dia->fecha->locale('es')->translatedFormat('l d \d\e F')) }} al {{ $dia->fecha_fin->locale('es')->translatedFormat('l d \d\e F \d\e Y') }}
                                @else
                                    {{ ucfirst($dia->fecha->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }}
                                @endif
                            </h3>
                            <p>{{ $dia->motivo }}</p>
                        </div>
                        @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))<form method="POST" action="{{ route('cronogramas.dias.destroy', [$periodo, $dia]) }}">@csrf @method('DELETE')<button class="danger-button" type="submit">Eliminar</button></form>@endif
                    </article>
                @empty
                    <div class="empty-state"><h2>No hay suspensiones registradas</h2><p>Todos los días del período se consideran disponibles para clases.</p></div>
                @endforelse
            </div>
        </section>
    @else
        <section class="panel"><div class="empty-state"><h2>No hay períodos académicos</h2><p>Administración debe crear primero el período académico.</p></div></section>
    @endif
</x-layouts.aplicacion>
