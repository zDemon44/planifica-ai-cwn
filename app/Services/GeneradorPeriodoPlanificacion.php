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
        ?string $tema,
        ?string $indicaciones,
    ): array {
        $campos = [
            'tema', 'recursos', 'experiencia', 'dua_experiencia', 'reflexion', 'dua_reflexion',
            'conceptualizacion', 'dua_conceptualizacion', 'aplicacion', 'dua_aplicacion',
            'tecnica_evaluacion', 'instrumento_evaluacion',
        ];
        $camposErca = ['experiencia', 'reflexion', 'conceptualizacion', 'aplicacion'];
        $longitudesMaximas = [
            'tema' => 120,
            'recursos' => 350,
            'dua_experiencia' => 280,
            'dua_reflexion' => 280,
            'dua_conceptualizacion' => 280,
            'dua_aplicacion' => 280,
            'tecnica_evaluacion' => 320,
            'instrumento_evaluacion' => 850,
        ];
        $descripciones = [
            'tema' => 'Título breve y específico del contenido de la clase.',
            'recursos' => 'Lista completa y natural de los materiales concretos requeridos por todas las actividades del docente y los estudiantes, sin separarlos con etiquetas.',
            'experiencia' => 'Acciones completas en infinitivo para activar saberes previos y explorar el tema; una por línea y sin viñetas.',
            'dua_experiencia' => 'Apoyo DUA de la experiencia, redactado con acciones en infinitivo.',
            'reflexion' => 'Acciones completas en infinitivo para analizar la experiencia, comparar y plantear preguntas; una por línea y sin viñetas.',
            'dua_reflexion' => 'Apoyo DUA de la reflexión, redactado con acciones en infinitivo.',
            'conceptualizacion' => 'Acciones completas en infinitivo para explicar, representar y formalizar el conocimiento; una por línea y sin viñetas.',
            'dua_conceptualizacion' => 'Apoyo DUA de la conceptualización, redactado con acciones en infinitivo.',
            'aplicacion' => 'Acciones completas en infinitivo para practicar, transferir y demostrar el aprendizaje; una por línea y sin viñetas.',
            'dua_aplicacion' => 'Apoyo DUA de la aplicación, redactado con acciones en infinitivo.',
            'tecnica_evaluacion' => 'Método que utilizará el docente para evaluar el aprendizaje, acompañado de una explicación breve de cómo se aplicará en las actividades propuestas.',
            'instrumento_evaluacion' => 'Herramienta de evaluación completa y lista para usar: nombre, escala o forma de registro y criterios, descriptores, campos o preguntas concretas alineadas con el indicador.',
        ];
        $propiedades = collect($campos)->mapWithKeys(function (string $campo) use ($camposErca, $descripciones, $longitudesMaximas): array {
            if (in_array($campo, $camposErca, true)) {
                return [$campo => [
                    'type' => 'array',
                    'description' => $descripciones[$campo],
                    'items' => ['type' => 'string', 'maxLength' => 180],
                    'minItems' => 2,
                    'maxItems' => 3,
                ]];
            }

            return [$campo => [
                'type' => 'string',
                'description' => $descripciones[$campo],
                'maxLength' => $longitudesMaximas[$campo],
            ]];
        })->all();
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
        $contextoTema = filled($tema)
            ? "\nTema definido por el docente: {$tema}. Debes conservar este tema exactamente en el campo tema."
            : '';
        $contextoIndicaciones = filled($indicaciones)
            ? "\nIndicaciones literales del docente: {$indicaciones}. Son la fuente prioritaria de la propuesta: respeta exactamente sus números, páginas, ejercicios y materiales; no completes rangos ni agregues páginas cercanas."
            : '';
        $prompt = <<<PROMPT
Diseña un único bloque de clase de {$duracionMinutos} minutos para Educación General Básica de Ecuador.
Asignatura: {$asignacion->materia->nombre}.
Curso: {$curso->nombre}, paralelo {$asignacion->seccion->nombre}.
Destreza seleccionada: {$destreza->codigo} {$destreza->descripcion}
Indicador relacionado: {$indicador->codigo} {$indicador->descripcion}{$contextoUnidad}{$contextoTema}{$contextoIndicaciones}

Genera una planificación completa, específica, realizable y sin redundancias. Ajustarla al curso, indicador y {$duracionMinutos} minutos. No inventar códigos ni repetir la destreza como actividad.

REGLAS:
1. ERCA: generar dos o tres acciones diferentes por fase según lo que realmente necesite el aprendizaje; no usar siempre la misma cantidad. Cada elemento debe contener una sola acción desarrollada, SIN guiones, viñetas, números ni prefijos. Iniciar con infinitivo. Precisar qué hará el estudiante, con qué contenido o material y con qué finalidad; evitar frases vagas y no acumular varias acciones en una sola oración.
2. EXPERIENCIA: activar saberes e interés mediante una situación cercana y exploración concreta con objetos, imágenes, materiales, juego, cuento o demostración. No formalizar conceptos todavía.
3. REFLEXIÓN: comparar lo observado, describir relaciones, expresar razonamientos y dialogar a partir de preguntas específicas como «¿...?» que conduzcan al concepto sin responderlo aún.
4. CONCEPTUALIZACIÓN: explicar y formalizar el concepto, regla o procedimiento; representarlo con ejemplos, lectura, esquemas u organizadores y comprobar su comprensión. Precisar el contenido, no decir solo «explicar el tema».
5. APLICACIÓN: proponer práctica pertinente, resolver una tarea concreta, transferir a una situación nueva o cotidiana y obtener evidencia observable del indicador. Sin relleno.
6. Mantener continuidad: reflexionar sobre la experiencia, conceptualizar sus interrogantes y aplicar lo aprendido.
7. DUA: redactar en infinitivo un apoyo específico por fase: participación en experiencia, expresión en reflexión, representaciones en conceptualización y formas de acción/respuesta en aplicación. Texto plano, sin JSON interno ni frases genéricas.
8. RECURSOS: lista natural y completa, sin «Docente:» ni «Estudiantes:». Incluir lo pedido y los materiales ordinarios que realmente requieren las actividades, sin inventar. Conservar literalmente páginas, ejercicios y números; nunca ampliar «página 13» a un rango.
9. TÉCNICA: indicar el método de evaluación y cómo aplicarlo (observación, preguntas, debate, exposición, ejercicios, análisis de trabajos o prueba).
10. INSTRUMENTO: no devolver solamente nombres. Entregarlo listo para usar y alineado con el indicador. Si es lista de cotejo/ficha, incluir escala y entre tres y cinco criterios observables; si es rúbrica/escala, criterios, niveles y descriptores; si es cuestionario/prueba, preguntas o ejercicios; si es registro anecdótico, campos y desempeño observado.
11. Alinear técnica e instrumento con aplicación e indicador. No evaluar contenidos no trabajados ni usar criterios genéricos.

Máximos: tema 15 palabras; recursos 45; cada fase ERCA 140; cada DUA 45; técnica 45; instrumento 180. Devolver solo el objeto JSON del esquema con todos los campos.
PROMPT;

        $ultimoError = null;
        foreach ([false, true] as $esReintento) {
            $promptActual = $esReintento
                ? $prompt."\n\nREINTENTO: la respuesta anterior no cumplió el JSON o produjo fases ERCA demasiado breves. Completa los 12 campos y desarrolla cada fase con acciones específicas, suficientes y conectadas entre sí. Escribe cada acción en una línea nueva sin guiones ni viñetas."
                : $prompt;
            $respuesta = Http::baseUrl(rtrim((string) config('services.ollama.url'), '/'))
                ->connectTimeout((int) config('services.ollama.connect_timeout'))
                ->timeout((int) config('services.ollama.timeout'))
                ->post('/api/chat', [
                    'model' => config('services.ollama.model'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un especialista ecuatoriano en planificación microcurricular, ciclo ERCA y Diseño Universal para el Aprendizaje. Redacta siempre en español y expresa todas las acciones pedagógicas con verbos en infinitivo.',
                        ],
                        ['role' => 'user', 'content' => $promptActual],
                    ],
                    'stream' => false,
                    'think' => false,
                    'format' => $esquema,
                    'options' => [
                        'temperature' => 0.1,
                        'num_predict' => $esReintento ? 950 : 850,
                    ],
                    'keep_alive' => '10m',
                ])->throw();

            try {
                $propuesta = $this->normalizarFasesErca(
                    $this->normalizarReferenciasPaginas(
                        $this->normalizarCamposDua(
                            $this->normalizarRecursos(
                                $this->decodificarPropuesta((string) $respuesta->json('message.content'), $campos),
                            ),
                        ),
                        $indicaciones,
                    ),
                );
                $validador = Validator::make($propuesta, collect($campos)->mapWithKeys(fn (string $campo): array => [
                    $campo => ['required', 'string', 'max:5000'],
                ])->all());
                if ($validador->fails()) {
                    throw new UnexpectedValueException('La propuesta generada está incompleta.');
                }

                /** @var array<string, string> $datos */
                $datos = $validador->validated();
                $this->validarProfundidadErca($datos);
                if (filled($tema)) {
                    $datos['tema'] = $tema;
                }

                return $datos;
            } catch (UnexpectedValueException $exception) {
                $ultimoError = $exception;
            }
        }

        throw $ultimoError ?? new UnexpectedValueException('Ollama no devolvió una propuesta válida.');
    }

    /** @param array<string, mixed> $propuesta
     * @return array<string, mixed>
     */
    private function normalizarFasesErca(array $propuesta): array
    {
        foreach (['experiencia', 'reflexion', 'conceptualizacion', 'aplicacion'] as $campo) {
            $valor = $propuesta[$campo] ?? null;
            if (! is_string($valor) && ! is_array($valor)) {
                continue;
            }

            $lineas = is_array($valor)
                ? array_values(array_filter(array_map(
                    fn (mixed $linea): string => is_scalar($linea) ? trim((string) $linea) : '',
                    $valor,
                )))
                : (preg_split('/\R+/u', trim($valor)) ?: []);
            $lineas = array_values(array_filter(array_map(
                fn (string $linea): string => trim(preg_replace('/^(?:[-–—*•]+|\d+[.)])\s*/u', '', trim($linea)) ?? $linea),
                $lineas,
            )));
            $propuesta[$campo] = implode("\n", $lineas);
        }

        return $propuesta;
    }

    /** @param array<string, string> $propuesta */
    private function validarProfundidadErca(array $propuesta): void
    {
        foreach (['experiencia', 'reflexion', 'conceptualizacion', 'aplicacion'] as $campo) {
            $contenido = trim($propuesta[$campo] ?? '');
            $lineas = array_values(array_filter(preg_split('/\R+/u', $contenido) ?: [], 'strlen'));
            preg_match_all('/[\p{L}\p{N}]+/u', $contenido, $palabras);

            if (count($lineas) < 2 || count($palabras[0]) < 20) {
                throw new UnexpectedValueException("La fase {$campo} necesita un desarrollo más completo.");
            }
        }
    }

    /** @param array<string, mixed> $propuesta
     * @return array<string, mixed>
     */
    private function normalizarRecursos(array $propuesta): array
    {
        $recursos = $propuesta['recursos'] ?? null;
        if (! is_string($recursos) || ! preg_match('/\b(?:docente|estudiantes?)\s*:/iu', $recursos)) {
            return $propuesta;
        }

        $elementos = preg_split('/\s*\b(?:docente|estudiantes?)\s*:\s*/iu', trim($recursos), -1, PREG_SPLIT_NO_EMPTY);
        $elementos = array_values(array_filter(array_map(
            fn (string $elemento): string => trim($elemento, " \t\n\r\0\x0B,;."),
            $elementos ?: [],
        )));

        if ($elementos !== []) {
            $propuesta['recursos'] = implode(', ', $elementos).'.';
        }

        return $propuesta;
    }

    /** @param array<string, mixed> $propuesta
     * @return array<string, mixed>
     */
    private function normalizarReferenciasPaginas(array $propuesta, ?string $indicaciones): array
    {
        $patronPagina = '/\bp[aá]ginas?\s+\d+(?:\s*(?:-|–|a|,|y)\s*\d+)*/iu';
        if (! is_string($indicaciones) || ! preg_match($patronPagina, $indicaciones, $referenciaSolicitada)) {
            return $propuesta;
        }

        $recursos = $propuesta['recursos'] ?? null;
        if (is_string($recursos)) {
            $propuesta['recursos'] = preg_replace(
                $patronPagina,
                $referenciaSolicitada[0],
                $recursos,
            ) ?? $recursos;
        }

        return $propuesta;
    }

    /** @param array<string, mixed> $propuesta
     * @return array<string, mixed>
     */
    private function normalizarCamposDua(array $propuesta): array
    {
        $camposDua = [
            'dua_experiencia', 'dua_reflexion', 'dua_conceptualizacion', 'dua_aplicacion',
        ];

        foreach ($camposDua as $campo) {
            $valor = $propuesta[$campo] ?? null;
            if (is_string($valor)) {
                $posibleJson = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $valor) ?? $valor);
                $decodificado = json_decode($posibleJson, true);
                if (is_array($decodificado)) {
                    $valor = $decodificado;
                }
            }

            if (! is_array($valor)) {
                continue;
            }

            $lineas = [];
            $etiquetas = [
                'participacion' => 'Participación',
                'representacion' => 'Representación',
                'expresion' => 'Expresión',
            ];
            foreach ($valor as $clave => $descripcion) {
                if (! is_scalar($descripcion) || trim((string) $descripcion) === '') {
                    continue;
                }
                $claveNormalizada = strtr(mb_strtolower((string) $clave), [
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                ]);
                $etiqueta = $etiquetas[$claveNormalizada] ?? ucfirst(str_replace('_', ' ', (string) $clave));
                $lineas[] = "{$etiqueta}: ".trim((string) $descripcion);
            }

            if ($lineas !== []) {
                $propuesta[$campo] = implode("\n", $lineas);
            }
        }

        return $propuesta;
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
