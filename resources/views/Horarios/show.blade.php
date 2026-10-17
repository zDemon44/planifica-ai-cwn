<x-layouts.aplicacion title="Horario semanal">
    <div class="page-heading">
        <div>
            <p class="eyebrow">{{ $horario->periodo->nombre }}</p>
            <h1>{{ $horario->seccion->curso->nombre }} · {{ $horario->seccion->nombre }}</h1>
            <p>Docente responsable: {{ $horario->docente->nombres }} {{ $horario->docente->apellidos }}</p>
        </div>
        <div class="planning-toolbar-actions">
            <a class="secondary-button" href="{{ route(auth()->user()->role === 'DOCENTE' ? 'dashboard' : 'horarios.index') }}">Volver</a>
            @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true) && ! $horario->periodo->cerrado)
                <a class="primary-button" href="{{ route('horarios.edit', $horario) }}">Editar horario</a>
            @endif
        </div>
    </div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($horario->periodo->cerrado)<p class="legacy-note">Este período está cerrado. El horario se conserva como historial.</p>@endif
    @php
        $franjas = $horario->bloques
            ->map(fn ($bloque) => $bloque->hora_inicio.'-'.$bloque->hora_fin)
            ->when($recreo, fn ($rangos) => $rangos->push($recreo->hora_inicio.'-'.$recreo->hora_fin))
            ->unique()->sort()->values();
    @endphp
    <section class="panel schedule-week" aria-label="Horario semanal">
        <div class="schedule-grid-scroll">
            <table class="schedule-grid">
                <thead><tr><th class="schedule-time-heading" scope="col">Hora</th>@foreach(App\Models\Horario::DIAS as $dia)<th scope="col">{{ $dia }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($franjas as $franja)
                        @php
                            [$horaInicio, $horaFin] = explode('-', $franja);
                            $esRecreo = $recreo && substr($recreo->hora_inicio, 0, 5) === substr($horaInicio, 0, 5) && substr($recreo->hora_fin, 0, 5) === substr($horaFin, 0, 5);
                        @endphp
                        <tr @class(['schedule-recess-row' => $esRecreo])>
                            <th class="schedule-time" scope="row"><time>{{ substr($horaInicio, 0, 5) }}</time><span>{{ substr($horaFin, 0, 5) }}</span></th>
                            @foreach(App\Models\Horario::DIAS as $numero => $dia)
                                @php($bloque = $horario->bloques->first(fn ($candidato) => $candidato->dia_semana === $numero && $candidato->hora_inicio === $horaInicio && $candidato->hora_fin === $horaFin))
                                <td>
                                    @if($esRecreo && in_array($numero, $recreo->dias_semana, true))
                                        <strong class="schedule-recess-name">{{ $recreo->nombre }}</strong>
                                    @elseif($bloque)
                                        <strong class="schedule-subject-name">{{ $bloque->materia->nombre }}</strong>
                                    @else
                                        <span class="schedule-free">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel schedule-summary">
        <div><span>Materias</span><strong>{{ $horario->bloques->pluck('materia_id')->unique()->count() }}</strong></div>
        <div><span>Períodos semanales</span><strong>{{ $horario->bloques->count() }}</strong></div>
        <div><span>Modelo docente</span><strong>Docente de curso</strong></div>
    </section>
</x-layouts.aplicacion>
