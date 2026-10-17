<x-layouts.aplicacion title="Materias">
    <div class="page-heading"><div><p class="eyebrow">ORGANIZACIÓN ACADÉMICA</p><h1>Materias</h1></div><a class="primary-button" href="{{ route('materias.create') }}">+ Nueva materia</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-list">
        <form method="GET" action="{{ route('materias.index') }}" class="list-toolbar">
            <div class="field"><label for="busqueda">Buscar materias</label><input id="busqueda" name="busqueda" value="{{ $busqueda }}" maxlength="100"></div>
            <div class="form-actions"><button class="primary-button">Buscar</button><a class="secondary-button" href="{{ route('materias.index') }}">Limpiar</a></div>
        </form>
        <div class="table-scroll"><table><thead><tr><th>Materia</th><th>Estado</th><th>Asignaciones</th><th>Acciones</th></tr></thead><tbody>
        @forelse($materias as $materia)
            <tr><td>{{ $materia->nombre }}</td><td>{{ ucfirst($materia->estado) }}</td><td>{{ $materia->asignaciones_count }}</td><td><div class="row-actions"><a href="{{ route('materias.show', $materia) }}">Ver</a><a href="{{ route('materias.edit', $materia) }}">Editar</a></div></td></tr>
        @empty<tr><td colspan="5">No se encontraron registros.</td></tr>@endforelse
        </tbody></table></div>
        <div class="pagination"><span>{{ $materias->total() }} resultados · Página {{ $materias->currentPage() }}</span><div>@if($materias->previousPageUrl())<a class="secondary-button" href="{{ $materias->previousPageUrl() }}">Anterior</a>@endif @if($materias->nextPageUrl())<a class="secondary-button" href="{{ $materias->nextPageUrl() }}">Siguiente</a>@endif</div></div>
    </section>
</x-layouts.aplicacion>
