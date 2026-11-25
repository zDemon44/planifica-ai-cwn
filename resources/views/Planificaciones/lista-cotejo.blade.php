@php
    $titulo = $instrumento['titulo'] ?? 'Lista de cotejo';
    [$tipo, $subtitulo] = str_contains($titulo, ':')
        ? array_map('trim', explode(':', $titulo, 2))
        : ['Lista de cotejo', $titulo];
    $asignacion = $planificacion->asignacion;
    $coloresNiveles = ['#f2d36b', '#b9d3ee', '#c9a24a', '#d9e6c3', '#e8c6c6'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $tipo }} · {{ $subtitulo }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #15243a; font-family: "DejaVu Sans", sans-serif; font-size: 9.5px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #30445f; padding: 4px 6px; vertical-align: middle; }
        .encabezado td { border: 0; padding: 0 0 6px; }
        .encabezado .logo { width: 52px; }
        .encabezado img { width: 46px; height: auto; }
        .encabezado .escuela { color: #173b72; font-size: 10px; }
        .encabezado .escuela strong { display: block; font-size: 12px; }
        .titulo { padding: 5px; border: 1px solid #30445f; background: #f2d36b; color: #102d58; font-size: 12px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: .4px; }
        .subtitulo { padding: 3px 6px; border: 1px solid #30445f; border-top: 0; color: #173b72; font-weight: bold; text-align: center; }
        .datos td { border-width: 0 0 1px; padding: 4px 2px; }
        .datos .etiqueta { width: 15%; font-weight: bold; }
        .datos .valor { width: 35%; }
        .instrucciones { margin: 6px 0; }
        .criterios { margin-top: 2px; }
        .criterios thead th { background: #fbe9c4; color: #102d58; font-weight: bold; text-align: center; }
        .criterios .cumplimiento { background: #173b72; color: #fff; text-transform: uppercase; letter-spacing: .4px; }
        .criterios .criterio { width: 34%; background: #fdf3dd; font-weight: bold; text-align: left; }
        .criterios .nivel { text-align: center; }
        .criterios tbody td { height: 34px; }
        .criterios .observaciones { width: 30%; }
        .seccion { background: #173b72; color: #fff; font-size: 10.5px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: .4px; }
        .valoracion { margin-top: 10px; }
        .valoracion .cabecera th { background: #f2d36b; color: #102d58; text-align: center; }
        .valoracion .cabecera th.nivel-cabecera { background: #b9d3ee; }
        .valoracion .cabecera th.escala-cabecera { background: #e7b65a; }
        .valoracion .centro { width: 22%; text-align: center; font-weight: bold; }
        .cierre { margin-top: 10px; }
        .cierre td { width: 50%; height: 70px; vertical-align: top; font-weight: bold; }
        .pie { margin-top: 8px; color: #52627c; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <table class="encabezado">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" alt="Emblema CWN"></td>
            @endif
            <td class="escuela">Escuela de Educación Básica<strong>Carmen Wither Navarro</strong></td>
        </tr>
    </table>

    <div class="titulo">{{ $tipo }}</div>
    <div class="subtitulo">{{ $subtitulo }}</div>

    <table class="datos">
        <tr>
            <td class="etiqueta">Institución:</td>
            <td class="valor">Escuela de Educación Básica Carmen Wither Navarro</td>
            <td class="etiqueta">Tema:</td>
            <td class="valor">{{ $periodo->tema ?: '' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Asignatura o área:</td>
            <td class="valor">{{ $asignacion->materia->nombre }}</td>
            <td class="etiqueta">Grado o grupo:</td>
            <td class="valor">{{ $asignacion->seccion->curso->nombre }} «{{ $asignacion->seccion->nombre }}»</td>
        </tr>
        <tr>
            <td class="etiqueta">Alumno:</td>
            <td class="valor"></td>
            <td class="etiqueta">Fecha:</td>
            <td class="valor"></td>
        </tr>
    </table>

    <p class="instrucciones"><strong>Instrucciones:</strong> Marque con una X el nivel de cumplimiento que el estudiante demostró en cada criterio y registre las observaciones sobre los aspectos que aún debe mejorar.</p>

    <table class="criterios">
        <thead>
            <tr>
                <th rowspan="2" class="criterio">Criterios de evaluación</th>
                <th colspan="{{ count($niveles) }}" class="cumplimiento">Cumplimiento</th>
                <th rowspan="2" class="observaciones">Observaciones</th>
            </tr>
            <tr>
                @foreach ($niveles as $posicion => $nivel)
                    <th class="nivel" style="background: {{ $coloresNiveles[$posicion] ?? '#eef2f8' }}">{{ $nivel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($instrumento['criterios'] as $criterio)
                <tr>
                    <td class="criterio">{{ $criterio['texto'] }}</td>
                    @foreach ($niveles as $nivel)
                        <td class="nivel"></td>
                    @endforeach
                    <td class="observaciones"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="valoracion">
        <tr><th colspan="3" class="seccion">Resultados de valoración</th></tr>
        <tr class="cabecera">
            <th>Valoración de los criterios</th>
            <th class="nivel-cabecera">Nivel de desempeño</th>
            <th class="escala-cabecera">Escala de valoración numérica</th>
        </tr>
        @foreach ($valoracion as $fila)
            <tr>
                <td>El estudiante {{ Str::lcfirst($fila['descripcion']) }}</td>
                <td class="centro">{{ $fila['nivel'] }}</td>
                <td class="centro">{{ $fila['escala'] }}</td>
            </tr>
        @endforeach
    </table>

    <table class="cierre">
        <tr>
            <td>Observaciones derivadas de las evidencias y resultados:</td>
            <td>Sugerencias de mejora:</td>
        </tr>
    </table>

    <p class="pie">{{ config('app.name') }} · Semana {{ $planificacion->numero_semana }} · Bloque {{ $periodo->orden }}</p>
</body>
</html>
