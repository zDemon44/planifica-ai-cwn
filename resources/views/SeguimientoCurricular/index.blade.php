<x-layouts.aplicacion title="Seguimiento curricular">
    <div class="page-heading curriculum-tracking-heading">
        <div><p class="eyebrow">AVANCE DE PLANIFICACIONES</p><h1>Seguimiento curricular</h1><p>Consulta qué destrezas e indicadores ya se han utilizado y cuántas veces aparecen en las planificaciones.</p></div>
        @if($opciones->isNotEmpty())
            <div class="field curriculum-scope"><label for="curriculum_scope">Materia y grado</label><select id="curriculum_scope">@foreach($opciones as $opcion)<option value="{{ route('seguimiento-curricular.index', ['materia_id' => $opcion['materia_id'], 'grado' => $opcion['grado']]) }}" @selected($seleccion && $seleccion['materia_id'] === $opcion['materia_id'] && $seleccion['grado'] === $opcion['grado'])>{{ $opcion['materia'] }} · {{ $opcion['grado'] }}.º grado</option>@endforeach</select></div>
        @endif
    </div>

    @if($seleccion)
        @php
            $destrezasUsadas = $destrezas->filter(fn($destreza) => isset($usosDestrezas[$destreza->id]))->count();
            $indicadores = $destrezas->flatMap->indicadores->unique('id');
            $indicadoresUsados = $indicadores->filter(fn($indicador) => isset($usosIndicadores[$indicador->id]))->count();
            $apariciones = $usosDestrezas->sum('usos') + $usosIndicadores->sum('usos');
        @endphp
        <section class="tracking-summary" aria-label="Resumen de uso curricular">
            <article><span>Materia</span><strong>{{ $materia->nombre }}</strong><small>{{ $seleccion['grado'] }}.º grado</small></article>
            <article><span>Destrezas utilizadas</span><strong>{{ $destrezasUsadas }} / {{ $destrezas->count() }}</strong><small>{{ $destrezas->count() ? round(($destrezasUsadas / $destrezas->count()) * 100) : 0 }}% del catálogo</small></article>
            <article><span>Indicadores utilizados</span><strong>{{ $indicadoresUsados }} / {{ $indicadores->count() }}</strong><small>{{ $indicadores->count() ? round(($indicadoresUsados / $indicadores->count()) * 100) : 0 }}% del catálogo</small></article>
            <article><span>Apariciones registradas</span><strong>{{ $apariciones }}</strong><small>En bloques de planificación</small></article>
        </section>

        <section class="panel tracking-panel">
            <div class="tracking-toolbar"><div><h2>Matriz de destrezas e indicadores</h2><p>La relación conserva los grupos definidos en el Excel institucional.</p></div><div class="tracking-legend"><span><i class="is-used"></i>Utilizado</span><span><i></i>Pendiente</span></div></div>
            <div class="tracking-table-wrap">
                <table class="tracking-table">
                    <thead><tr><th>Grupo</th><th>Destrezas con criterios de desempeño</th><th>Competencia</th><th>Usos</th><th>Indicadores de evaluación relacionados</th><th>Usos</th></tr></thead>
                    @forelse($destrezas->groupBy('grupo') as $grupo => $registros)
                        <tbody>
                            @foreach($registros as $destreza)
                                @php($usoDestreza = $usosDestrezas->get($destreza->id))
                                <tr>
                                    @if($loop->first)<th class="tracking-group" rowspan="{{ $registros->count() }}">{{ $grupo }}</th>@endif
                                    <td class="tracking-curricular-cell {{ $usoDestreza ? 'is-used' : '' }}"><p>{{ $destreza->descripcion }}</p></td>
                                    <td class="tracking-competence {{ $usoDestreza ? 'is-used' : '' }}">{{ $destreza->competencias->pluck('codigo')->implode(', ') ?: '—' }}</td>
                                    <td class="tracking-count {{ $usoDestreza ? 'is-used' : '' }}"><strong>{{ $usoDestreza?->usos ?? 0 }}</strong><small>{{ $usoDestreza?->planificaciones ?? 0 }} planif.</small></td>
                                    <td class="tracking-indicators">@foreach($destreza->indicadores as $indicador)@php($usoIndicador = $usosIndicadores->get($indicador->id))<div class="tracking-indicator {{ $usoIndicador ? 'is-used' : '' }}"><p>{{ $indicador->descripcion }}</p>@foreach($indicador->competencias as $competencia)<span>{{ $competencia->codigo }}</span>@endforeach</div>@endforeach</td>
                                    <td class="tracking-indicator-counts">@foreach($destreza->indicadores as $indicador)@php($usoIndicador = $usosIndicadores->get($indicador->id))<div class="tracking-count {{ $usoIndicador ? 'is-used' : '' }}"><strong>{{ $usoIndicador?->usos ?? 0 }}</strong><small>{{ $usoIndicador?->planificaciones ?? 0 }} planif.</small></div>@endforeach</td>
                                </tr>
                            @endforeach
                        </tbody>
                    @empty
                        <tbody><tr><td colspan="6"><div class="empty-state"><h2>No hay catálogo para esta selección</h2><p>El administrador debe importar las destrezas y los indicadores de la materia.</p></div></td></tr></tbody>
                    @endforelse
                </table>
            </div>
        </section>
    @else
        <section class="panel"><div class="empty-state"><h2>No hay materias disponibles</h2><p>Primero se debe importar un catálogo curricular y asignar la materia al docente.</p></div></section>
    @endif

    <script>document.getElementById('curriculum_scope')?.addEventListener('change',event=>window.location.assign(event.target.value));</script>
</x-layouts.aplicacion>
