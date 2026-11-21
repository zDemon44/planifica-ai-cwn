        <aside class="sidebar" id="sidebar" aria-label="Barra lateral">
            <div class="sidebar-header">
                <a class="brand" href="{{ route('dashboard') }}" data-sidebar-label="Panel principal">
                    <img src="{{ asset('images/logo-cwn.png') }}" alt="Emblema CWN" width="58" height="58">
                    <span class="brand-copy"><strong>Planifica AI</strong><small>CWN · PORTAL EDUCATIVO</small></span>
                </a>
            </div>
            <div class="school-name">Escuela de Educación Básica<br><strong>Carmen Wither Navarro</strong></div>
            <nav aria-label="Navegación principal">
                <p class="nav-caption">MI ESPACIO</p>
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}" data-sidebar-label="Panel principal"
                    @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                    <svg aria-hidden="true"><use href="#i-grid"/></svg><span class="nav-label">Panel principal</span>
                </a>
                @if(auth()->user()->role === 'SUPERADMIN')
                    <a class="nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"
                        href="{{ route('usuarios.index') }}" data-sidebar-label="Usuarios"
                        @if(request()->routeIs('usuarios.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-users"/></svg><span class="nav-label">Usuarios</span>
                    </a>
                @endif
                <p class="nav-caption second-caption">GESTIÓN ACADÉMICA</p>
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN', 'DOCENTE'], true))
                    @php($etiquetaHorario = auth()->user()->role === 'DOCENTE' ? 'Mi horario' : 'Horarios')
                    <a class="nav-item {{ request()->routeIs('horarios.*') ? 'active' : '' }}"
                        href="{{ route('horarios.index') }}" data-sidebar-label="{{ $etiquetaHorario }}"
                        @if(request()->routeIs('horarios.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-calendar"/></svg><span class="nav-label">{{ $etiquetaHorario }}</span>
                    </a>
                    <a class="nav-item {{ request()->routeIs('cronogramas.*') ? 'active' : '' }}"
                        href="{{ route('cronogramas.index') }}" data-sidebar-label="Cronograma lectivo"
                        @if(request()->routeIs('cronogramas.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-calendar"/></svg><span class="nav-label">Cronograma lectivo</span>
                    </a>
                @endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
                    <a class="nav-item {{ request()->routeIs('recreos.*') ? 'active' : '' }}"
                        href="{{ route('recreos.index') }}" data-sidebar-label="Recreos"
                        @if(request()->routeIs('recreos.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-calendar"/></svg><span class="nav-label">Recreos</span>
                    </a>
                @endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN', 'DOCENTE'], true))
                    <a class="nav-item {{ request()->routeIs('planificaciones.*') ? 'active' : '' }}"
                        href="{{ route('planificaciones.index') }}" data-sidebar-label="Planificaciones"
                        @if(request()->routeIs('planificaciones.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-book"/></svg><span class="nav-label">Planificaciones</span>
                    </a>
                    <a class="nav-item {{ request()->routeIs('seguimiento-curricular.*') ? 'active' : '' }}"
                        href="{{ route('seguimiento-curricular.index') }}" data-sidebar-label="Seguimiento curricular"
                        @if(request()->routeIs('seguimiento-curricular.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-check"/></svg><span class="nav-label">Seguimiento curricular</span>
                    </a>
                @endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
                    <a class="nav-item {{ request()->routeIs('academico.catalogo') ? 'active' : '' }}"
                        href="{{ route('academico.catalogo') }}" data-sidebar-label="Cursos y períodos"
                        @if(request()->routeIs('academico.catalogo')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-book"/></svg><span class="nav-label">Cursos y períodos</span>
                    </a>
                    <a class="nav-item {{ request()->routeIs('catalogo-curricular.*') ? 'active' : '' }}"
                        href="{{ route('catalogo-curricular.index') }}" data-sidebar-label="Catálogo curricular"
                        @if(request()->routeIs('catalogo-curricular.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-book"/></svg><span class="nav-label">Catálogo curricular</span>
                    </a>
                    <a class="nav-item {{ request()->routeIs('docentes.*') ? 'active' : '' }}"
                        href="{{ route('docentes.index') }}" data-sidebar-label="Docentes"
                        @if(request()->routeIs('docentes.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-users"/></svg><span class="nav-label">Docentes</span>
                    </a>
                    <a class="nav-item {{ request()->routeIs('materias.*') ? 'active' : '' }}"
                        href="{{ route('materias.index') }}" data-sidebar-label="Materias"
                        @if(request()->routeIs('materias.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-book"/></svg><span class="nav-label">Materias</span>
                    </a>
                    <a class="nav-item {{ request()->routeIs('asignaciones.*') ? 'active' : '' }}"
                        href="{{ route('asignaciones.index') }}" data-sidebar-label="Asignaciones"
                        @if(request()->routeIs('asignaciones.*')) aria-current="page" @endif>
                        <svg aria-hidden="true"><use href="#i-grid"/></svg><span class="nav-label">Asignaciones</span>
                    </a>
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="school-note"><span>✦</span><strong>Crecer, aprender, compartir.</strong><p>Juntos construimos el futuro<br>de nuestra comunidad.</p></div>
                <span class="sidebar-foot">CWN · Comunidad educativa</span>
            </div>
        </aside>
