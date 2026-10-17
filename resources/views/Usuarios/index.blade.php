<x-layouts.aplicacion title="Usuarios">
    <div class="page-heading"><div><h1>Usuarios y accesos</h1><p>Cuentas de acceso del personal del colegio.</p></div><a class="primary-button" href="{{ route('usuarios.create') }}">+ Nuevo usuario</a></div>
    @if(session('success'))<p class="success-message" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="delete-confirmation" role="alert"><ul>@foreach($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul></div>@endif
    <section class="panel record-list">
        <form method="GET" action="{{ route('usuarios.index') }}" class="list-toolbar"><div class="field"><label for="busqueda">Buscar por nombre o correo</label><input id="busqueda" name="busqueda" value="{{ $busqueda }}" maxlength="100"></div><div class="form-actions"><button class="primary-button">Buscar</button><a class="secondary-button" href="{{ route('usuarios.index') }}">Limpiar</a></div></form>
        <div class="table-scroll"><table><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Docente vinculado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($usuarios as $usuario)
                <tr><td>{{ $usuario->name }}<small>{{ $usuario->email }}</small></td><td>{{ $roles[$usuario->role] ?? 'Sin permisos asignados' }}</td><td>{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</td><td>{{ $usuario->docente ? $usuario->docente->nombres.' '.$usuario->docente->apellidos : '—' }}</td><td><div class="row-actions"><a href="{{ route('usuarios.show', $usuario) }}">Ver</a><a href="{{ route('usuarios.edit', $usuario) }}">Editar</a></div></td></tr>
            @empty<tr><td colspan="5">No se encontraron usuarios.</td></tr>@endforelse
        </tbody></table></div>
        <div class="pagination"><span>{{ $usuarios->total() }} usuarios</span><div>@if($usuarios->previousPageUrl())<a class="secondary-button" href="{{ $usuarios->previousPageUrl() }}">Anterior</a>@endif @if($usuarios->nextPageUrl())<a class="secondary-button" href="{{ $usuarios->nextPageUrl() }}">Siguiente</a>@endif</div></div>
    </section>
</x-layouts.aplicacion>
