<x-layouts.aplicacion title="Usuarios">
    <div class="page-heading"><h1>{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1><a class="secondary-button" href="{{ route('usuarios.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
            @csrf
            @if($usuario->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="field"><label for="name">Nombre *</label><input id="name" name="name" value="{{ old('name', $usuario->name) }}" maxlength="100" required></div>
                <div class="field"><label for="email">Correo de acceso *</label><input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" maxlength="255" autocomplete="off" required></div>
                <div class="field"><label for="role">Rol *</label><select id="role" name="role" required><option value="">Selecciona un rol</option>@foreach($roles as $valor => $nombre)<option value="{{ $valor }}" @selected(old('role', $usuario->role ?? 'ADMIN') === $valor)>{{ $nombre }}</option>@endforeach</select></div>
                <div class="field"><label for="activo">Estado de la cuenta *</label><select id="activo" name="activo"><option value="1" @selected((string) old('activo', (int) $usuario->activo) === '1')>Activo</option><option value="0" @selected((string) old('activo', (int) $usuario->activo) === '0')>Inactivo</option></select></div>
                <div class="field"><label for="docente_id">Ficha del profesor (solo para rol Docente)</label><select id="docente_id" name="docente_id"><option value="">Sin vincular</option>@foreach($docentes as $docente)<option value="{{ $docente->id }}" @selected(old('docente_id', $usuario->docente_id) == $docente->id)>{{ $docente->nombres }} {{ $docente->apellidos }} · {{ $docente->identificacion }}</option>@endforeach</select><small>Para otros roles selecciona Sin vincular.</small></div>
                <div class="field"><label for="password">{{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña *' }}</label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" @required(!$usuario->exists)><small>{{ $usuario->exists ? 'Déjala vacía para conservar la actual.' : 'Mínimo 8 caracteres.' }}</small></div>
                <div class="field"><label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password"></div>
            </div>
            <p class="muted">Superadmin administra las cuentas. Administrador gestiona la configuración académica. Docente consulta sus materias, horarios y planificaciones.</p>
            <p class="muted">Cambiar el acceso o la contraseña cierra las sesiones anteriores. Desactivar conserva los registros históricos.</p>
            <div class="form-actions"><a class="secondary-button" href="{{ $usuario->exists ? route('usuarios.show', $usuario) : route('usuarios.index') }}">Cancelar</a><button class="primary-button">Guardar usuario</button></div>
        </form>
    </section>
</x-layouts.aplicacion>
