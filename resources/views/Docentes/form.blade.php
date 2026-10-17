<x-layouts.aplicacion title="Docentes">
    <div class="page-heading"><h1>{{ $docente->exists ? 'Editar docente' : 'Nuevo docente' }}</h1><a class="secondary-button" href="{{ route('docentes.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <p class="muted">Los campos con * son obligatorios.</p>
        <form method="POST" action="{{ $docente->exists ? route('docentes.update', $docente) : route('docentes.store') }}">
            @csrf
            @if($docente->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="field">
                    <label for="nombres">Nombres *</label>
                    <input id="nombres" name="nombres" type="text" value="{{ old('nombres', $docente->nombres) }}" maxlength="100" required>
                    @error('nombres')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="apellidos">Apellidos *</label>
                    <input id="apellidos" name="apellidos" type="text" value="{{ old('apellidos', $docente->apellidos) }}" maxlength="100" required>
                    @error('apellidos')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="identificacion">Identificación *</label>
                    <input id="identificacion" name="identificacion" type="text" value="{{ old('identificacion', $docente->identificacion) }}" maxlength="30" required>
                    @error('identificacion')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="correo">Correo</label>
                    <input id="correo" name="correo" type="email" value="{{ old('correo', $docente->correo) }}" maxlength="255">
                    @error('correo')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $docente->telefono) }}" maxlength="30">
                    @error('telefono')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="field"><label for="estado">Estado *</label><select id="estado" name="estado" required><option value="activo" @selected(old('estado', $docente->estado ?? 'activo') === 'activo')>Activo</option><option value="inactivo" @selected(old('estado', $docente->estado) === 'inactivo')>Inactivo</option></select></div>
            </div>
            <div class="form-actions"><a class="secondary-button" href="{{ $docente->exists ? route('docentes.show', $docente) : route('docentes.index') }}">Cancelar</a><button class="primary-button">Guardar docente</button></div>
        </form>
    </section>
</x-layouts.aplicacion>
