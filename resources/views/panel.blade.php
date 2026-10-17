<x-layouts.aplicacion>
    <div class="page-heading">
        <div><p class="eyebrow">GESTIÓN ACADÉMICA</p><h1>Panel principal</h1><p>Administra la estructura necesaria para las planificaciones semanales.</p></div>
    </div>
    <section class="welcome-banner" aria-label="Bienvenida">
        <div><span class="welcome-label">BIENVENIDO A TU ESPACIO</span><h2>Hola, {{ auth()->user()->name }} <span class="greeting-dot">.</span></h2><p>Configura docentes, materias y horarios para organizar las planificaciones académicas.</p><a href="{{ route('planificaciones.index') }}">Ver planificaciones <span aria-hidden="true">→</span></a></div>
        <div class="banner-art" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><span class="star star-one">✦</span><img src="{{ asset('images/logo-cwn.png') }}" alt="" width="160" height="160"><span class="star star-two">✧</span></div>
    </section>
    <div class="section-heading"><h2>Accesos principales</h2><span>Planificación académica semanal</span></div>
    <section class="course-grid">
        @foreach([
            ['Planificaciones', 'Crea, revisa y aprueba planificaciones semanales.', 'planificaciones.index', 'book'],
            ['Horarios', 'Organiza los períodos de clase por docente y curso.', 'horarios.index', 'calendar'],
            ['Docentes', 'Administra la información de los docentes.', 'docentes.index', 'users'],
            ['Materias', 'Configura las materias disponibles.', 'materias.index', 'book'],
            ['Asignaciones', 'Relaciona docentes, materias y cursos.', 'asignaciones.index', 'grid'],
            ['Cursos y períodos', 'Gestiona cursos, paralelos y períodos académicos.', 'academico.catalogo', 'calendar'],
        ] as [$titulo, $descripcion, $ruta, $icono])
            <article class="panel course-card">
                <div class="stat-top"><h2>{{ $titulo }}</h2><span class="icon-box green"><svg><use href="#i-{{ $icono }}"/></svg></span></div>
                <p>{{ $descripcion }}</p>
                <div class="form-actions"><a class="primary-button" href="{{ route($ruta) }}">Ingresar</a></div>
            </article>
        @endforeach
    </section>
</x-layouts.aplicacion>
