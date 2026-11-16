<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\DestrezaCurricular;
use App\Models\IndicadorEvaluacion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsistentePlanificacionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_docente_puede_sincronizar_el_token_del_asistente(): void
    {
        [, , , $docente] = $this->contextoCurricular();

        $this->actingAs($docente)
            ->getJson(route('planificaciones.asistente.token'))
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_docente_genera_un_periodo_estructurado_desde_su_destreza(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $propuesta = $this->propuesta();
        $propuesta['recursos'] = 'Docente: Páginas 12-14 del libro de texto. Estudiantes: hojas de papel y lápiz.';
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => json_encode($propuesta)],
                'done' => true,
            ]),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 90,
            'titulo_unidad' => 'Aprendemos con patrones',
            'tema' => 'Secuencias con material concreto',
            'indicaciones' => 'Prioriza el trabajo en parejas, materiales reciclados y los ejercicios de la página 13.',
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('message', 'Propuesta generada. Revísala antes de guardar el borrador.')
            ->assertJsonPath('propuesta.tema', 'Secuencias con material concreto')
            ->assertJsonPath('propuesta.recursos', 'página 13 del libro de texto, hojas de papel y lápiz.')
            ->assertJsonPath('propuesta.experiencia', str_replace('- ', '', $propuesta['experiencia']))
            ->assertJsonPath('propuesta.dua_aplicacion', 'Permitir expresar la respuesta mediante material concreto o dibujo.');
        Http::assertSent(function (Request $request) use ($asignacion, $destreza): bool {
            $datos = $request->data();

            return $request->url() === 'http://127.0.0.1:11434/api/chat'
                && $datos['model'] === 'qwen3.5:9b'
                && $datos['stream'] === false
                && $datos['think'] === false
                && $datos['format']['type'] === 'object'
                && $datos['format']['properties']['experiencia']['type'] === 'array'
                && $datos['format']['properties']['experiencia']['minItems'] === 2
                && $datos['format']['properties']['experiencia']['maxItems'] === 3
                && $datos['format']['properties']['experiencia']['items']['maxLength'] === 180
                && str_contains($datos['format']['properties']['recursos']['description'], 'sin separarlos')
                && str_contains($datos['format']['properties']['experiencia']['description'], 'infinitivo')
                && str_contains($datos['format']['properties']['experiencia']['description'], 'Acciones completas')
                && str_contains($datos['format']['properties']['instrumento_evaluacion']['description'], 'completa y lista para usar')
                && $datos['options']['num_predict'] === 850
                && str_contains($datos['messages'][1]['content'], $asignacion->materia->nombre)
                && str_contains($datos['messages'][1]['content'], $destreza->descripcion)
                && str_contains($datos['messages'][1]['content'], '90 minutos')
                && str_contains($datos['messages'][1]['content'], 'Secuencias con material concreto')
                && str_contains($datos['messages'][1]['content'], 'materiales reciclados')
                && str_contains($datos['messages'][1]['content'], 'página 13')
                && str_contains($datos['messages'][1]['content'], 'Iniciar con infinitivo')
                && str_contains($datos['messages'][1]['content'], 'EXPERIENCIA')
                && str_contains($datos['messages'][1]['content'], 'dialogar a partir de preguntas')
                && str_contains($datos['messages'][1]['content'], 'dos o tres acciones')
                && str_contains($datos['messages'][1]['content'], 'SIN guiones')
                && str_contains($datos['messages'][1]['content'], 'no devolver solamente nombres')
                && str_contains($datos['messages'][1]['content'], 'entre tres y cinco criterios')
                && str_contains($datos['messages'][1]['content'], 'sin «Docente:»')
                && str_contains($datos['messages'][1]['content'], 'materiales ordinarios');
        });
    }

    public function test_rechaza_un_indicador_no_relacionado_sin_consultar_a_ollama(): void
    {
        [$asignacion, $destreza, , $docente] = $this->contextoCurricular();
        $indicadorAjeno = IndicadorEvaluacion::create([
            'materia_id' => $asignacion->materia_id,
            'grado' => $asignacion->seccion->curso->grado,
            'codigo' => 'I.M.2.9.9',
            'descripcion' => 'Indicador no relacionado.',
            'grupo' => 9,
            'activo' => true,
        ]);
        Http::preventStrayRequests();

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicadorAjeno->id,
            'duracion_minutos' => 45,
        ]);

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors('indicador_evaluacion_id')
            ->assertJsonPath('message', 'Selecciona una destreza y uno de sus indicadores relacionados.');
        Http::assertNothingSent();
    }

    public function test_convierte_un_objeto_json_de_dua_en_texto_legible(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $propuesta = $this->propuesta();
        $propuesta['dua_experiencia'] = json_encode([
            'participacion' => 'Describir oralmente los patrones observados.',
            'representacion' => 'Dibujar los patrones en el cuaderno.',
            'expresion' => 'Explicar el patrón con palabras o gestos.',
        ], JSON_UNESCAPED_UNICODE);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => json_encode($propuesta)],
            ]),
        ]);

        $this->actingAs($docente)
            ->postJson(route('planificaciones.asistente.periodos.store'), [
                'asignacion_id' => $asignacion->id,
                'destreza_curricular_id' => $destreza->id,
                'indicador_evaluacion_id' => $indicador->id,
                'duracion_minutos' => 45,
            ])
            ->assertOk()
            ->assertJsonPath('propuesta.dua_experiencia', "Participación: Describir oralmente los patrones observados.\nRepresentación: Dibujar los patrones en el cuaderno.\nExpresión: Explicar el patrón con palabras o gestos.");
    }

    public function test_corrige_saltos_de_linea_invalidos_en_la_respuesta_de_ollama(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $propuesta = $this->propuesta();
        $propuesta['tema'] = 'Patrones "con objetos" del entorno';
        $contenido = json_encode($propuesta, JSON_UNESCAPED_UNICODE);
        $contenido = preg_replace('/^\{/', '{/n', $contenido);
        $contenido = str_replace([
            '\\"con objetos\\"',
            '","',
        ], [
            '"con objetos"',
            '",/n"',
        ], $contenido);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => $contenido],
                'done' => true,
            ]),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('propuesta.tema', 'Patrones "con objetos" del entorno');
        Http::assertSentCount(1);
    }

    public function test_reintenta_automaticamente_una_respuesta_irrecuperable(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::sequence()
                ->push(['message' => ['role' => 'assistant', 'content' => '{respuesta truncada']])
                ->push(['message' => ['role' => 'assistant', 'content' => json_encode($this->propuesta())]]),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 90,
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('propuesta.tema', 'Patrones numéricos del entorno');
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            $datos = $request->data();

            return $datos['options']['num_predict'] === 950
                && str_contains($datos['messages'][1]['content'], 'REINTENTO');
        });
    }

    public function test_reintenta_cuando_una_fase_erca_es_demasiado_pobre(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $pobre = $this->propuesta();
        $pobre['experiencia'] = 'Observar una secuencia.';
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::sequence()
                ->push(['message' => ['role' => 'assistant', 'content' => json_encode($pobre)]])
                ->push(['message' => ['role' => 'assistant', 'content' => json_encode($this->propuesta())]]),
        ]);

        $this->actingAs($docente)
            ->postJson(route('planificaciones.asistente.periodos.store'), [
                'asignacion_id' => $asignacion->id,
                'destreza_curricular_id' => $destreza->id,
                'indicador_evaluacion_id' => $indicador->id,
                'duracion_minutos' => 45,
            ])
            ->assertOk()
            ->assertJsonPath('propuesta.experiencia', str_replace('- ', '', $this->propuesta()['experiencia']));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->data()['messages'][1]['content'],
            'fases ERCA demasiado breves',
        ));
    }

    public function test_acepta_una_cantidad_variable_de_acciones_en_las_fases_erca(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $propuesta = $this->propuesta();
        $propuesta['experiencia'] = "- Observar una secuencia numérica del entorno para recuperar conocimientos previos sobre el orden de sus elementos.\n- Manipular tarjetas numeradas para continuar la secuencia y explicar cómo cambia cada término respecto al anterior.";
        $propuesta['conceptualizacion'] .= "\n- Representar la regla de formación mediante una expresión sencilla.";
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => json_encode($propuesta)],
            ]),
        ]);

        $this->actingAs($docente)
            ->postJson(route('planificaciones.asistente.periodos.store'), [
                'asignacion_id' => $asignacion->id,
                'destreza_curricular_id' => $destreza->id,
                'indicador_evaluacion_id' => $indicador->id,
                'duracion_minutos' => 45,
            ])
            ->assertOk()
            ->assertJsonPath('propuesta.experiencia', str_replace('- ', '', $propuesta['experiencia']))
            ->assertJsonPath('propuesta.conceptualizacion', str_replace('- ', '', $propuesta['conceptualizacion']));

        Http::assertSentCount(1);
    }

    public function test_docente_no_puede_generar_para_una_asignacion_ajena(): void
    {
        [$asignacion, $destreza, $indicador] = $this->contextoCurricular();
        $otraAsignacion = Asignacion::factory()->create();
        $docenteAjeno = User::factory()->create([
            'role' => 'DOCENTE',
            'docente_id' => $otraAsignacion->docente_id,
        ]);
        Http::preventStrayRequests();

        $respuesta = $this->actingAs($docenteAjeno)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
        ]);

        $respuesta->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_solo_docentes_autenticados_pueden_usar_el_asistente(): void
    {
        [$asignacion, $destreza, $indicador] = $this->contextoCurricular();
        $datos = [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
        ];
        Http::preventStrayRequests();

        $this->postJson(route('planificaciones.asistente.periodos.store'), $datos)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
            ->postJson(route('planificaciones.asistente.periodos.store'), $datos)->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_muestra_un_error_util_cuando_ollama_no_esta_disponible(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::failedConnection('Ollama no disponible'),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
        ]);

        $respuesta->assertServiceUnavailable()
            ->assertJsonPath('message', 'No pudimos comunicarnos con el asistente. Verifica que Ollama esté abierto e inténtalo nuevamente.');
        Http::assertSentCount(1);
    }

    public function test_diferencia_un_timeout_de_ollama_de_una_desconexion(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:11434/api/chat' => Http::failedConnection('Operation timed out after 420007 milliseconds'),
        ]);

        $this->actingAs($docente)
            ->postJson(route('planificaciones.asistente.periodos.store'), [
                'asignacion_id' => $asignacion->id,
                'destreza_curricular_id' => $destreza->id,
                'indicador_evaluacion_id' => $indicador->id,
                'duracion_minutos' => 45,
            ])
            ->assertStatus(504)
            ->assertJsonPath('message', 'Ollama está abierto, pero la generación superó el tiempo máximo. Inténtalo nuevamente con indicaciones más breves.');

        Http::assertSentCount(1);
    }

    /** @return array{Asignacion, DestrezaCurricular, IndicadorEvaluacion, User} */
    private function contextoCurricular(): array
    {
        $asignacion = Asignacion::factory()->create();
        $asignacion->load('seccion.curso');
        $destreza = DestrezaCurricular::create([
            'materia_id' => $asignacion->materia_id,
            'grado' => $asignacion->seccion->curso->grado,
            'codigo' => 'M.2.1.1',
            'descripcion' => 'Construir patrones numéricos a partir de situaciones del entorno.',
            'grupo' => 1,
            'activo' => true,
        ]);
        $indicador = IndicadorEvaluacion::create([
            'materia_id' => $asignacion->materia_id,
            'grado' => $asignacion->seccion->curso->grado,
            'codigo' => 'I.M.2.1.1',
            'descripcion' => 'Completa y explica patrones numéricos sencillos.',
            'grupo' => 1,
            'activo' => true,
        ]);
        $destreza->indicadores()->attach($indicador);
        $docente = User::factory()->create([
            'role' => 'DOCENTE',
            'docente_id' => $asignacion->docente_id,
        ]);

        return [$asignacion, $destreza, $indicador, $docente];
    }

    /** @return array<string, string> */
    private function propuesta(): array
    {
        return [
            'tema' => 'Patrones numéricos del entorno',
            'recursos' => "Docente: tarjetas de patrones y pizarra.\nEstudiantes: tapas de colores y cuaderno.",
            'experiencia' => "- Observar secuencias construidas con tapas de colores.\n- Manipular las tapas para continuar cada patrón.\n- Identificar patrones semejantes en objetos del entorno.",
            'dua_experiencia' => 'Permitir explorar los patrones de forma visual y manipulativa.',
            'reflexion' => "- Comparar los cambios observados en cada secuencia.\n- Dialogar a partir de preguntas como: ¿qué cambia?, ¿qué se repite?\n- Explicar oralmente cómo continuar cada patrón.",
            'dua_reflexion' => 'Ofrecer responder de forma oral, escrita o señalada.',
            'conceptualizacion' => "- Reconocer la regla que forma un patrón numérico.\n- Definir la secuencia como un conjunto ordenado de elementos.\n- Organizar ejemplos mediante símbolos y colores.",
            'dua_conceptualizacion' => 'Presentar la regla mediante símbolos, colores y ejemplos.',
            'aplicacion' => "- Completar ejercicios de sucesiones numéricas.\n- Crear y compartir un patrón numérico propio.\n- Resolver una situación cotidiana aplicando una regla de formación.",
            'dua_aplicacion' => 'Permitir expresar la respuesta mediante material concreto o dibujo.',
            'tecnica_evaluacion' => 'Observación directa durante la construcción y explicación de las secuencias.',
            'instrumento_evaluacion' => "Lista de cotejo (Sí / En proceso / No)\n- Identificar correctamente la regla de formación.\n- Completar la secuencia respetando el patrón.\n- Explicar el procedimiento utilizado.",
        ];
    }
}
