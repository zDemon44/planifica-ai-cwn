<x-layouts.aplicacion title="Asignaciones">
    <div class="page-heading"><h1>{{ $asignacion->exists ? 'Editar asignación' : 'Nueva asignación' }}</h1><a class="secondary-button" href="{{ route('asignaciones.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <p>Selecciona docente, materia y curso/paralelo. El período corresponde a la sección.</p>
        @if($docentes->isEmpty() || $materias->isEmpty() || $secciones->isEmpty())
            <p class="legacy-note">Para continuar necesitas <a href="{{ route('docentes.index') }}">docentes activos</a>, <a href="{{ route('materias.index') }}">materias activas</a> y <a href="{{ route('academico.catalogo') }}">paralelos de un período abierto</a>.</p>
        @endif
        <form method="POST" action="{{ $asignacion->exists ? route('asignaciones.update', $asignacion) : route('asignaciones.store') }}">
            @csrf
            @if($asignacion->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="field"><label for="docente_id">Docente *</label><select id="docente_id" name="docente_id" required><option value="">Selecciona un docente</option>@foreach($docentes as $docente)<option value="{{ $docente->id }}" @selected(old('docente_id', $asignacion->docente_id) == $docente->id)>{{ $docente->nombres }} {{ $docente->apellidos }}</option>@endforeach</select></div>
                <div class="field"><label for="materia_id">Materia *</label><select id="materia_id" name="materia_id" required><option value="">Selecciona una materia</option>@foreach($materias as $materia)<option value="{{ $materia->id }}" @selected(old('materia_id', $asignacion->materia_id) == $materia->id)>{{ $materia->nombre }}</option>@endforeach</select></div>
                <div class="field"><label for="seccion_id">Curso / paralelo / período *</label><select id="seccion_id" name="seccion_id" required><option value="">Selecciona una sección</option>@foreach($secciones as $seccion)<option value="{{ $seccion->id }}" @selected(old('seccion_id', $asignacion->seccion_id) == $seccion->id)>{{ $seccion->etiqueta }}</option>@endforeach</select></div>
            </div>
            <div class="form-actions"><a class="secondary-button" href="{{ $asignacion->exists ? route('asignaciones.show', $asignacion) : route('asignaciones.index') }}">Cancelar</a><button class="primary-button" @disabled($docentes->isEmpty() || $materias->isEmpty() || $secciones->isEmpty())>Guardar asignación</button></div>
        </form>
    </section>
</x-layouts.aplicacion>
