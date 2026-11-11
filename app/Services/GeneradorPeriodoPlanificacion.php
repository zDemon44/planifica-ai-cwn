<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\DestrezaCurricular;
use App\Models\IndicadorEvaluacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use JsonException;
use UnexpectedValueException;

class GeneradorPeriodoPlanificacion
{
    /** @return array<string, string> */
    public function generar(
        Asignacion $asignacion,
        DestrezaCurricular $destreza,
        IndicadorEvaluacion $indicador,
        int $duracionMinutos,
        ?string $tituloUnidad,
    ): array {
        $campos = [
            'tema', 'recursos', 'experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion',
            'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion',
            'tecnica_evaluacion', 'instrumento_evaluacion',
        ];
        $propiedades = collect($campos)->mapWithKeys(fn (string $campo): array => [
            $campo => ['type' => 'string'],
        ])->all();
        $esquema = [
            'type' => 'object',
            'properties' => $propiedades,
            'required' => $campos,
            'additionalProperties' => false,
        ];

        $curso = $asignacion->seccion->curso;
        $contextoUnidad = filled($tituloUnidad)
            ? "\nTítulo de la unidad indicado por el docente: {$tituloUnidad}."
            : '';
        $prompt = <<<PROMPT
Diseña un único bloque de clase de {$duracionMinutos} minutos para Educación General Básica de Ecuador.
Asignatura: {$asignacion->materia->nombre}.
Curso: {$curso->nombre}, paralelo {$asignacion->seccion->nombre}.
Destreza seleccionada: {$destreza->codigo} {$destreza->descripcion}
Indicador relacionado: {$indicador->codigo} {$indicador->descripcion}{$contextoUnidad}

Genera contenido breve, concreto, realizable y completamente alineado con la destreza y el indicador. Distribuye las actividades de acuerdo con la duración. Aplica el ciclo ERCA: experiencia, reflexión, conceptualización y aplicación. Para cada fase escribe una adaptación DUA específica, indicando distintas formas de participación, representación o expresión. Propón recursos accesibles y una técnica con su instrumento de evaluación. No inventes códigos curriculares ni repitas la destreza como actividad.

Límites obligatorios: tema de máximo 15 palabras; recursos de máximo 35 palabras; cada fase ERCA de máximo 50 palabras; cada DUA de máximo 30 palabras; técnica e instrumento de máximo 30 palabras cada uno. Devuelve únicamente el objeto solicitado por el esquema JSON y completa todos sus campos.
PROMPT;

        $ultimoError = null;
        foreach ([false, true] as $esReintento) {
            $promptActual = $esReintento
                ? $prompt."\n\nREINTENTO: la respuesta anterior no tuvo un JSON válido. Completa los 12 campos, usa frases más cortas y escribe el objeto JSON en una sola línea."
                : $prompt;
            $respuesta = Http::baseUrl(rtrim((string) config('services.ollama.url'), '/'))
                ->connectTimeout((int) config('services.ollama.connect_timeout'))
                ->timeout((int) config('services.ollama.timeout'))
                ->post('/api/chat', [
                    'model' => config('services.ollama.model'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un especialista ecuatoriano en planificación microcurricular, ciclo ERCA y Diseño Universal para el Aprendizaje. Redacta siempre en español.',
                        ],
                        ['role' => 'user', 'content' => $promptActual],
                    ],
                    'stream' => false,
                    'format' => $esquema,
                    'options' => [
                        'temperature' => 0,
                        'num_predict' => $esReintento ? 1800 : 1400,
                    ],
                    'keep_alive' => '10m',
                ])->throw();

            try {
                $propuesta = $this->decodificarPropuesta((string) $respuesta->json('message.content'), $campos);
                $validador = Validator::make($propuesta, collect($campos)->mapWithKeys(fn (string $campo): array => [
                    $campo => ['required', 'string', 'max:5000'],
                ])->all());
                if ($validador->fails()) {
                    throw new UnexpectedValueException('La propuesta generada está incompleta.');
                }

                /** @var array<string, string> $datos */
                $datos = $validador->validated();

                return $datos;
            } catch (UnexpectedValueException $exception) {
                $ultimoError = $exception;
            }
        }

        throw $ultimoError ?? new UnexpectedValueException('Ollama no devolvió una propuesta válida.');
    }

    /**
     * @param  array<int, string>  $campos
     * @return array<string, mixed>
     */
    private function decodificarPropuesta(string $contenido, array $campos): array
    {
        $contenido = trim($contenido);
        $contenido = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $contenido) ?? $contenido;

        try {
            $propuesta = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $normalizado = preg_replace('/([{},])\s*[\\\\\/]n\s*(?="|[}\]])/', "$1\n", $contenido) ?? $contenido;
            $normalizado = $this->escaparCaracteresControlEnCadenas($normalizado);

            try {
                $propuesta = json_decode($normalizado, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $propuesta = $this->extraerCamposDeRespuesta($contenido, $campos);
                if ($propuesta === null) {
                    throw new UnexpectedValueException('Ollama devolvió una respuesta que no se pudo interpretar.', previous: $exception);
                }
            }
        }

        if (! is_array($propuesta)) {
            throw new UnexpectedValueException('Ollama no devolvió una propuesta válida.');
        }

        return $propuesta;
    }

    /**
     * @param  array<int, string>  $campos
     * @return array<string, string>|null
     */
    private function extraerCamposDeRespuesta(string $contenido, array $campos): ?array
    {
        $patronCampos = implode('|', array_map(fn (string $campo): string => preg_quote($campo, '/'), $campos));
        $propuesta = [];

        foreach ($campos as $campo) {
            if (! preg_match('/"'.preg_quote($campo, '/').'"\s*:\s*"/', $contenido, $inicio, PREG_OFFSET_CAPTURE)) {
                return null;
            }

            $posicionValor = $inicio[0][1] + strlen($inicio[0][0]);
            $contenidoRestante = substr($contenido, $posicionValor);
            $patronSiguiente = '/"\s*,?\s*(?:[\\\\\/]n)?\s*"(?:'.$patronCampos.')"\s*:/s';

            if (preg_match($patronSiguiente, $contenidoRestante, $siguiente, PREG_OFFSET_CAPTURE)) {
                $longitudValor = $siguiente[0][1];
            } elseif (preg_match('/"\s*(?:[\\\\\/]n)?\s*}\s*$/s', $contenidoRestante, $cierre, PREG_OFFSET_CAPTURE)) {
                $longitudValor = $cierre[0][1];
            } else {
                return null;
            }

            $valor = trim(substr($contenidoRestante, 0, $longitudValor));
            $valor = str_replace(
                ['\\"', '/n', '\\n', '\\r', '\\t'],
                ['"', "\n", "\n", "\r", "\t"],
                $valor,
            );
            if ($valor === '') {
                return null;
            }

            $propuesta[$campo] = $valor;
        }

        return $propuesta;
    }

    private function escaparCaracteresControlEnCadenas(string $contenido): string
    {
        $resultado = '';
        $dentroDeCadena = false;
        $caracterEscapado = false;

        for ($indice = 0, $longitud = strlen($contenido); $indice < $longitud; $indice++) {
            $caracter = $contenido[$indice];

            if (! $dentroDeCadena) {
                $dentroDeCadena = $caracter === '"';
                $resultado .= $caracter;

                continue;
            }

            if ($caracterEscapado) {
                $resultado .= $caracter;
                $caracterEscapado = false;

                continue;
            }

            if ($caracter === '\\') {
                $resultado .= $caracter;
                $caracterEscapado = true;

                continue;
            }

            if ($caracter === '"') {
                $dentroDeCadena = false;
                $resultado .= $caracter;

                continue;
            }

            $resultado .= match ($caracter) {
                "\n" => '\\n',
                "\r" => '\\r',
                "\t" => '\\t',
                default => ord($caracter) < 32 ? sprintf('\\u%04x', ord($caracter)) : $caracter,
            };
        }

        return $resultado;
    }
}
