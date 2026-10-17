        <aside class="sidebar" id="sidebar" aria-label="Barra lateral">
            <a class="brand" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-cwn.png') }}" alt="Emblema CWN" width="58" height="58">
                <span><strong>Planifica AI</strong><small>CWN · PORTAL EDUCATIVO</small></span>
            </a>
            <div class="school-name">Escuela de Educación Básica<br><strong>Carmen Wither Navarro</strong></div>
            <nav aria-label="Navegación principal">
                <p class="nav-caption">MI ESPACIO</p>
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><svg><use href="#i-grid"/></svg>Panel principal</a>
                @if(auth()->user()->role === 'DOCENTE')<a class="nav-item {{ request()->routeIs('portal-docente.*') ? 'active' : '' }}" href="{{ route('portal-docente.index') }}"><svg><use href="#i-book"/></svg>Mis materias</a>@endif
                @if(auth()->user()->role === 'SUPERADMIN')<a class="nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}"><svg><use href="#i-users"/></svg>Usuarios</a>@endif
                <p class="nav-caption second-caption">GESTIÓN ACADÉMICA</p>
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN', 'DOCENTE'], true))<a class="nav-item {{ request()->routeIs('horarios.*') ? 'active' : '' }}" href="{{ route('horarios.index') }}"><svg><use href="#i-calendar"/></svg>{{ auth()->user()->role === 'DOCENTE' ? 'Mi horario' : 'Horarios' }}</a>@endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))<a class="nav-item {{ request()->routeIs('recreos.*') ? 'active' : '' }}" href="{{ route('recreos.index') }}"><svg><use href="#i-calendar"/></svg>Recreos</a>@endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN', 'DOCENTE'], true))<a class="nav-item {{ request()->routeIs('planificaciones.*') ? 'active' : '' }}" href="{{ route('planificaciones.index') }}"><svg><use href="#i-book"/></svg>Planificaciones</a>@endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))<a class="nav-item {{ request()->routeIs('academico.catalogo') ? 'active' : '' }}" href="{{ route('academico.catalogo') }}"><svg><use href="#i-book"/></svg>Cursos y períodos</a>@endif
                @if(in_array(auth()->user()->role, ['SUPERADMIN', 'ADMIN'], true))
                    <a class="nav-item {{ request()->routeIs('docentes.*') ? 'active' : '' }}" href="{{ route('docentes.index') }}"><svg><use href="#i-users"/></svg>Docentes</a>
                    <a class="nav-item {{ request()->routeIs('materias.*') ? 'active' : '' }}" href="{{ route('materias.index') }}"><svg><use href="#i-book"/></svg>Materias</a>
                    <a class="nav-item {{ request()->routeIs('asignaciones.*') ? 'active' : '' }}" href="{{ route('asignaciones.index') }}"><svg><use href="#i-grid"/></svg>Asignaciones</a>
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="school-note"><span>✦</span><strong>Crecer, aprender, compartir.</strong><p>Juntos construimos el futuro<br>de nuestra comunidad.</p></div>
                <span class="sidebar-foot">CWN · Comunidad educativa</span>
            </div>
        </aside>
