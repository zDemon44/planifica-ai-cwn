<x-layouts.aplicacion title="Asignaciones">
    <div class="page-heading"><div><h1>Asignaciones docentes</h1><p>Materias, cursos y períodos de cada docente.</p></div><a class="primary-button" href="{{ route('asignaciones.create') }}">+ Nueva asignación</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-list">
        <form method="GET" action="{{ route('asignaciones.index') }}"><div class="form-grid">
            <div class="field"><label for="docente_id">Docente</label><select id="docente_id" name="docente_id"><option value="">Todos</option>@foreach($docentes as $docente)<option value="{{ $docente->id }}" @selected(($filtros['docente_id'] ?? '') == $docente->id)>{{ $docente->nombres }} {{ $docente->apellidos }}</option>@endforeach</select></div>
            <div class="field"><label for="periodo_id">Período</label><select id="periodo_id" name="periodo_id"><option value="">Todos</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected(($filtros['periodo_id'] ?? '') == $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
        </div><div class="form-actions"><a class="secondary-button" href="{{ route('asignaciones.index') }}">Limpiar</a><button class="primary-button">Filtrar</button></div></form>
        <div class="table-scroll"><table><thead><tr><th>Docente</th><th>Materia</th><th>Curso / paralelo</th><th>Período</th><th></th></tr></thead><tbody>
    @forelse($asignaciones as $asignacion)
        <tr><td>{{ $asignacion->docente->nombres }} {{ $asignacion->docente->apellidos }}</td><td>{{ $asignacion->materia->nombre }}</td><td>{{ $asignacion->seccion->curso->nombre }} · {{ $asignacion->seccion->nombre }}</td><td>{{ $asignacion->seccion->periodo->nombre }}<small>{{ $asignacion->seccion->periodo->cerrado ? 'Cerrado' : 'Abierto' }}</small></td><td><a class="secondary-button" href="{{ route('asignaciones.show', $asignacion) }}">Ver</a></td></tr>
    @empty<tr><td colspan="5">No hay asignaciones registradas.</td></tr>@endforelse
    </tbody></table></div><div class="pagination"><span>{{ $asignaciones->total() }} resultados · Página {{ $asignaciones->currentPage() }}</span><div>@if($asignaciones->previousPageUrl())<a class="secondary-button" href="{{ $asignaciones->previousPageUrl() }}">Anterior</a>@endif @if($asignaciones->nextPageUrl())<a class="secondary-button" href="{{ $asignaciones->nextPageUrl() }}">Siguiente</a>@endif</div></div>
    </section>
</x-layouts.aplicacion>
