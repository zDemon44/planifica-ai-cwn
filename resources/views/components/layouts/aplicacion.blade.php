@props(['title' => 'Panel principal'])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo-cwn.png') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    @if(request()->routeIs('planificaciones.*'))<link rel="stylesheet" href="{{ asset('css/planificaciones.css') }}?v={{ filemtime(public_path('css/planificaciones.css')) }}">@endif
    @if(request()->routeIs('horarios.*', 'recreos.*'))<link rel="stylesheet" href="{{ asset('css/horarios.css') }}?v={{ filemtime(public_path('css/horarios.css')) }}">@endif
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/gestion.css') }}?v={{ filemtime(public_path('css/gestion.css')) }}">@livewireStyles</head>
<body>
    <a class="skip-link" href="#principal">Ir al contenido</a>
    <svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
        <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 4v3"/></symbol>
        <symbol id="i-book" viewBox="0 0 24 24"><path d="M12 5v16M12 5C9 2 4 3 2 4v15c4-2 7-1 10 2 3-3 6-4 10-2V4c-2-1-7-2-10 1Z"/></symbol>
        <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18M7 15h3m4 0h3m-10 3h3"/></symbol>
        <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M9 21h6"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></symbol>
        <symbol id="i-exit" viewBox="0 0 24 24"><path d="M10 3H4v18h6m-1-9h12m-4-4 4 4-4 4"/></symbol>
    </svg>
    <div class="workspace">
<x-barra-lateral />        <button class="sidebar-backdrop" type="button" aria-label="Cerrar menú" hidden></button>
        <div class="main-area">
            <header class="topbar">
                <div class="breadcrumb"><button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar" hidden>☰</button><span>Mi espacio</span><span class="separator">/</span><strong>{{ $title }}</strong></div>
                <div class="topbar-actions">
                    <div class="account"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="account-text"><strong>{{ auth()->user()->name }}</strong><small>{{ $rolCuenta }}</small></span></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit"><svg aria-hidden="true"><use href="#i-exit"/></svg>Cerrar sesión</button></form>
                </div>
            </header>
            <main id="principal" tabindex="-1">
{{ $slot }}                <footer class="page-footer"><span>© {{ date('Y') }} Escuela de Educación Básica Carmen Wither Navarro</span><span>Hecho para nuestra comunidad.</span></footer>
            </main>
        </div>
    </div>
@livewireScripts</body>
</html>
