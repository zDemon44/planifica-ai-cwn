<x-layouts.aplicacion title="Materias">
    <div class="page-heading"><h1>{{ $materia->nombre }}</h1><a class="secondary-button" href="{{ route('materias.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <div class="panel-heading"><h2>Datos de materia</h2><a class="secondary-button" href="{{ route('materias.edit', $materia) }}">Editar</a></div>
        <dl class="detail-grid">
            <div><dt>Nombre</dt><dd>{{ $materia->nombre ?: 'Sin registrar' }}</dd></div>
            <div><dt>Descripción</dt><dd>{{ $materia->descripcion ?: 'Sin registrar' }}</dd></div>
            <div><dt>Estado</dt><dd>{{ ucfirst($materia->estado) }}</dd></div>
        </dl>
    </section>
    <section class="panel record-list"><div class="panel-heading"><h2>Asignaciones e historial</h2><a class="primary-button" href="{{ route('asignaciones.create') }}">Nueva asignación</a></div>
        <div class="table-scroll"><table><thead><tr><th>Docente</th><th>Materia</th><th>Curso / paralelo</th><th>Período</th><th></th></tr></thead><tbody>
    @forelse($asignaciones as $asignacion)
        <tr><td>{{ $asignacion->docente->nombres }} {{ $asignacion->docente->apellidos }}</td><td>{{ $asignacion->materia->nombre }}</td><td>{{ $asignacion->seccion->curso->nombre }} · {{ $asignacion->seccion->nombre }}</td><td>{{ $asignacion->seccion->periodo->nombre }}<small>{{ $asignacion->seccion->periodo->cerrado ? 'Cerrado' : 'Abierto' }}</small></td><td><a class="secondary-button" href="{{ route('asignaciones.show', $asignacion) }}">Ver</a></td></tr>
    @empty<tr><td colspan="5">No hay asignaciones registradas.</td></tr>@endforelse
    </tbody></table></div><div class="pagination"><span>{{ $asignaciones->total() }} resultados · Página {{ $asignaciones->currentPage() }}</span><div>@if($asignaciones->previousPageUrl())<a class="secondary-button" href="{{ $asignaciones->previousPageUrl() }}">Anterior</a>@endif @if($asignaciones->nextPageUrl())<a class="secondary-button" href="{{ $asignaciones->nextPageUrl() }}">Siguiente</a>@endif</div></div>
    </section>
    <section class="delete-confirmation"><h2>Eliminar materia</h2><p>Si tiene asignaciones, cambia su estado a inactivo para conservar el historial.</p><form method="POST" action="{{ route('materias.destroy', $materia) }}" onsubmit="return confirm('¿Eliminar este registro permanentemente?')">@csrf @method('DELETE')<button class="danger-button" @disabled($asignaciones->total() > 0)>Eliminar</button></form></section>
</x-layouts.aplicacion>
