<x-layouts.aplicacion title="Docentes">
    <div class="page-heading"><div><p class="eyebrow">ORGANIZACIÓN ACADÉMICA</p><h1>Docentes</h1></div><a class="primary-button" href="{{ route('docentes.create') }}">+ Nuevo docente</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-list">
        <form method="GET" action="{{ route('docentes.index') }}" class="list-toolbar">
            <div class="field"><label for="busqueda">Buscar docentes</label><input id="busqueda" name="busqueda" value="{{ $busqueda }}" maxlength="100"></div>
            <div class="form-actions"><button class="primary-button">Buscar</button><a class="secondary-button" href="{{ route('docentes.index') }}">Limpiar</a></div>
        </form>
        <div class="table-scroll"><table><thead><tr><th>Docente</th><th>Identificación</th><th>Estado</th><th>Asignaciones</th><th>Acciones</th></tr></thead><tbody>
        @forelse($docentes as $docente)
            <tr><td>{{ $docente->nombres }} {{ $docente->apellidos }}</td><td>{{ $docente->identificacion }}</td><td>{{ ucfirst($docente->estado) }}</td><td>{{ $docente->asignaciones_count }}</td><td><div class="row-actions"><a href="{{ route('docentes.show', $docente) }}">Ver</a><a href="{{ route('docentes.edit', $docente) }}">Editar</a></div></td></tr>
        @empty<tr><td colspan="5">No se encontraron registros.</td></tr>@endforelse
        </tbody></table></div>
        <div class="pagination"><span>{{ $docentes->total() }} resultados · Página {{ $docentes->currentPage() }}</span><div>@if($docentes->previousPageUrl())<a class="secondary-button" href="{{ $docentes->previousPageUrl() }}">Anterior</a>@endif @if($docentes->nextPageUrl())<a class="secondary-button" href="{{ $docentes->nextPageUrl() }}">Siguiente</a>@endif</div></div>
    </section>
</x-layouts.aplicacion>
