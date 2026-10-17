<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo-cwn.png') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
    <script src="{{ asset('js/login.js') }}" defer></script>
</head>
<body>
    <main class="login-shell">
        <section class="identity" aria-label="Escuela de Educación Básica Carmen Wither Navarro">
            <div class="institution"><span class="small-mark">CWN</span><span>ESCUELA DE EDUCACIÓN BÁSICA<br><strong>CARMEN WITHER NAVARRO</strong></span></div>
            <div class="identity-content">
                <div class="crest"><img src="{{ asset('images/logo-cwn.png') }}" alt="Emblema de la Escuela de Educación Básica Carmen Wither Navarro" width="240" height="240"></div>
                <span class="eyebrow">CRECEMOS JUNTOS</span>
                <h1>Un lugar para aprender.<br><em>Un futuro por construir.</em></h1>
                <p>Bienvenido a nuestra comunidad educativa.<br>Tu próximo paso comienza aquí.</p>
            </div>
            <div class="identity-footer"><span class="gold-line"></span> Educación que nos une.</div>
        </section>
        <section class="access" aria-labelledby="login-title">
            <div class="portal-label"><span></span> PORTAL EDUCATIVO</div>
            <div class="form-content">
                <span class="eyebrow wine">BIENVENIDO A PLANIFICA AI</span>
                <h2 id="login-title">Iniciar sesión</h2>
                <p class="intro">Ingresa tus credenciales para acceder a tu espacio.</p>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="error" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <label for="email">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Ingresa tu correo electrónico" autocomplete="username" required @error('email') aria-invalid="true" @enderror>
                    <label for="password">Contraseña</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" placeholder="Ingresa tu contraseña" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                        <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false" hidden>Mostrar</button>
                    </div>
                    <label class="remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Recordar mi sesión</label>
                    <button class="submit" type="submit">Ingresar <span aria-hidden="true">→</span></button>
                </form>
                <div class="help"><strong>¿Necesitas ayuda para ingresar?</strong><p>Comunícate con la administración del colegio para obtener o recuperar tus credenciales.</p></div>
            </div>
            <footer>© {{ date('Y') }} CWN <span>Escuela de Educación Básica Carmen Wither Navarro</span></footer>
        </section>
    </main>
</body>
</html>
