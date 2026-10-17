<x-layouts.aplicacion title="Asignaciones">
    <div class="page-heading"><h1>Asignación docente</h1><a class="secondary-button" href="{{ route('asignaciones.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <dl class="detail-grid">
            <div><dt>Docente</dt><dd><a href="{{ route('docentes.show', $asignacion->docente) }}">{{ $asignacion->docente->nombres }} {{ $asignacion->docente->apellidos }}</a></dd></div>
            <div><dt>Materia</dt><dd><a href="{{ route('materias.show', $asignacion->materia) }}">{{ $asignacion->materia->nombre }}</a></dd></div>
            <div><dt>Curso / paralelo</dt><dd>{{ $asignacion->seccion->curso->nombre }} · {{ $asignacion->seccion->nombre }}</dd></div>
            <div><dt>Período</dt><dd>{{ $asignacion->seccion->periodo->nombre }}</dd></div>
        </dl>
        @if($asignacion->seccion->periodo->cerrado)
            <p class="legacy-note">Período cerrado. Esta asignación se conserva como historial y no puede modificarse.</p>
        @elseif($asignacion->planificaciones_count > 0)
            <p class="legacy-note">Esta asignación tiene planificaciones. Se conserva como historial y no puede cambiar de docente, materia o curso.</p>
        @else
            <div class="form-actions"><a class="secondary-button" href="{{ route('asignaciones.edit', $asignacion) }}">Editar asignación</a></div>
        @endif
    </section>
    @unless($asignacion->seccion->periodo->cerrado || $asignacion->planificaciones_count > 0)
        <section class="delete-confirmation"><h2>Eliminar asignación</h2><p>Elimina únicamente registros incorrectos. Para el siguiente año crea una asignación nueva.</p><form method="POST" action="{{ route('asignaciones.destroy', $asignacion) }}" onsubmit="return confirm('¿Eliminar esta asignación?')">@csrf @method('DELETE')<button class="danger-button">Eliminar asignación</button></form></section>
    @endunless
</x-layouts.aplicacion>
