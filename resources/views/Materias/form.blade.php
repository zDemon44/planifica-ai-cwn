<x-layouts.aplicacion title="Materias">
    <div class="page-heading"><h1>{{ $materia->exists ? 'Editar materia' : 'Nueva materia' }}</h1><a class="secondary-button" href="{{ route('materias.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <p class="muted">Los campos con * son obligatorios.</p>
        <form method="POST" action="{{ $materia->exists ? route('materias.update', $materia) : route('materias.store') }}">
            @csrf
            @if($materia->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="field">
                    <label for="nombre">Nombre *</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $materia->nombre) }}" maxlength="100" required>
                    @error('nombre')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4" maxlength="3000">{{ old('descripcion', $materia->descripcion) }}</textarea>
                    @error('descripcion')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field"><label for="estado">Estado *</label><select id="estado" name="estado" required><option value="activo" @selected(old('estado', $materia->estado ?? 'activo') === 'activo')>Activo</option><option value="inactivo" @selected(old('estado', $materia->estado) === 'inactivo')>Inactivo</option></select></div>
            </div>
            <div class="form-actions"><a class="secondary-button" href="{{ $materia->exists ? route('materias.show', $materia) : route('materias.index') }}">Cancelar</a><button class="primary-button">Guardar materia</button></div>
        </form>
    </section>
</x-layouts.aplicacion>
