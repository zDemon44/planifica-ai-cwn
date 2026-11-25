@props(['texto', 'clave', 'descarga' => null])
@php
    $instrumento = app(\App\Services\LectorInstrumentoEvaluacion::class)->leer($texto);
@endphp
@if ($instrumento)
    @php($niveles = $instrumento['escala'] ?: ['Logrado', 'No logrado'])
    <table class="planning-instrument-matrix" data-instrumento>
        @if ($instrumento['titulo'])
            <caption>{{ $instrumento['titulo'] }}</caption>
        @endif
        <thead>
            <tr>
                <th class="instrument-indicator">Indicadores</th>
                @foreach ($niveles as $nivel)
                    <th class="instrument-level">{{ $nivel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($instrumento['criterios'] as $indice => $criterio)
                <tr>
                    <td class="instrument-indicator">{{ $criterio['texto'] }}</td>
                    @foreach ($niveles as $posicion => $nivel)
                        <td class="instrument-level">
                            <input class="instrument-mark" type="radio" name="instrumento-{{ $clave }}-{{ $indice }}"
                                value="{{ $posicion }}" aria-label="{{ $nivel }}: {{ $criterio['texto'] }}">
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th class="instrument-total-label">Totales</th>
                @foreach ($niveles as $posicion => $nivel)
                    <td class="instrument-level instrument-count" data-total-nivel="{{ $posicion }}"></td>
                @endforeach
            </tr>
        </tfoot>
    </table>
    @if ($descarga)
        <a class="instrument-download" href="{{ $descarga }}">⬇ Descargar lista de cotejo (PDF)</a>
    @endif
@else
    <p>{{ $texto ?: '—' }}</p>
@endif
