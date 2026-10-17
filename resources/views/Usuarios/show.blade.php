<x-layouts.aplicacion title="Usuarios">
    <div class="page-heading"><h1>{{ $usuario->name }}</h1><a class="secondary-button" href="{{ route('usuarios.index') }}">Volver al listado</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-editor">
        <div class="panel-heading"><h2>Cuenta de acceso</h2><a class="secondary-button" href="{{ route('usuarios.edit', $usuario) }}">Editar acceso o contraseña</a></div>
        <dl class="detail-grid">
            <div><dt>Correo de acceso</dt><dd>{{ $usuario->email }}</dd></div>
            <div><dt>Rol</dt><dd>{{ $roles[$usuario->role] ?? 'Sin permisos asignados' }}</dd></div>
            <div><dt>Estado</dt><dd>{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</dd></div>
            <div><dt>Ficha docente</dt><dd>@if($usuario->docente)<a href="{{ route('docentes.show', $usuario->docente) }}">{{ $usuario->docente->nombres }} {{ $usuario->docente->apellidos }}</a>@else Sin vincular @endif</dd></div>
        </dl>
        <p class="muted">La contraseña no se muestra. Puedes establecer una nueva desde Editar acceso o contraseña.</p>
    </section>
</x-layouts.aplicacion>
