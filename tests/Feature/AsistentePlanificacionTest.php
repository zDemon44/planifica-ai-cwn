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
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::response(
                $this->respuestaGemini((string) json_encode($propuesta)),
            ),
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

            $esquema = $datos['generationConfig']['responseJsonSchema'];
            $prompt = $datos['contents'][0]['parts'][0]['text'];

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent'
                && $request->hasHeader('x-goog-api-key')
                && $datos['generationConfig']['responseMimeType'] === 'application/json'
                && $esquema['type'] === 'object'
                && $esquema['properties']['experiencia']['type'] === 'array'
                && $esquema['properties']['experiencia']['minItems'] === 2
                && $esquema['properties']['experiencia']['maxItems'] === 3
                && $esquema['properties']['experiencia']['items']['maxLength'] === 180
                && str_contains($esquema['properties']['recursos']['description'], 'sin separarlos')
                && str_contains($esquema['properties']['experiencia']['description'], 'infinitivo')
                && str_contains($esquema['properties']['experiencia']['description'], 'Acciones completas')
                && str_contains($esquema['properties']['instrumento_evaluacion']['description'], 'completa y lista para usar')
                && $datos['generationConfig']['maxOutputTokens'] === 850
                && str_contains($datos['systemInstruction']['parts'][0]['text'], 'especialista ecuatoriano')
                && str_contains($prompt, $asignacion->materia->nombre)
                && str_contains($prompt, $destreza->descripcion)
                && str_contains($prompt, '90 minutos')
                && str_contains($prompt, 'Secuencias con material concreto')
                && str_contains($prompt, 'materiales reciclados')
                && str_contains($prompt, 'página 13')
                && str_contains($prompt, 'Iniciar con infinitivo')
                && str_contains($prompt, 'EXPERIENCIA')
                && str_contains($prompt, 'dialogar a partir de preguntas')
                && str_contains($prompt, 'dos o tres acciones')
                && str_contains($prompt, 'SIN guiones')
                && str_contains($prompt, 'no devolver solamente nombres')
                && str_contains($prompt, 'entre tres y cinco criterios')
                && str_contains($prompt, 'sin «Docente:»')
                && str_contains($prompt, 'materiales ordinarios');
        });
    }

    public function test_genera_solo_el_campo_enfocado_usando_el_contenido_actual_del_bloque(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response($this->respuestaGemini((string) json_encode([
                'aplicacion' => [
                    'Resolver en parejas tres sucesiones numéricas con tapas de colores.',
                    'Crear una sucesión propia y explicar a otro grupo la regla que la forma.',
                ],
            ]))),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
            'tema' => 'Sucesiones con tapas',
            'enfoque' => 'aplicacion',
            'indicaciones' => 'Trabajo en parejas.',
            'contenido_actual' => [
                'experiencia' => 'Observar secuencias construidas con tapas de colores.',
                'aplicacion' => 'Completar ejercicios del libro.',
                'recursos' => '',
            ],
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('message', 'Propuesta generada para «Aplicación». Revísala antes de guardar el borrador.')
            ->assertJsonPath('propuesta.aplicacion', "Resolver en parejas tres sucesiones numéricas con tapas de colores.\nCrear una sucesión propia y explicar a otro grupo la regla que la forma.")
            ->assertJsonMissingPath('propuesta.tema')
            ->assertJsonMissingPath('propuesta.recursos');
        Http::assertSent(function (Request $request): bool {
            $datos = $request->data();
            $esquema = $datos['generationConfig']['responseJsonSchema'];
            $prompt = $datos['contents'][0]['parts'][0]['text'];

            return array_keys($esquema['properties']) === ['aplicacion']
                && $esquema['required'] === ['aplicacion']
                && $datos['generationConfig']['maxOutputTokens'] === 550
                && str_contains($prompt, 'Redacta únicamente el campo «Aplicación»')
                && str_contains($prompt, 'Experiencia: Observar secuencias construidas con tapas de colores.')
                && str_contains($prompt, "Texto actual de «Aplicación»:\nCompletar ejercicios del libro.")
                && str_contains($prompt, 'APLICACIÓN: proponer práctica pertinente')
                && str_contains($prompt, 'Trabajo en parejas.')
                && ! str_contains($prompt, 'INSTRUMENTO');
        });
    }

    public function test_con_tema_libre_elige_una_destreza_y_un_indicador_vinculados(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $otraDestreza = DestrezaCurricular::create([
            'materia_id' => $asignacion->materia_id,
            'grado' => $asignacion->seccion->curso->grado,
            'codigo' => 'M.2.2.1',
            'descripcion' => 'Medir longitudes con unidades no convencionales.',
            'grupo' => 2,
            'activo' => true,
        ]);
        $otroIndicador = IndicadorEvaluacion::create([
            'materia_id' => $asignacion->materia_id,
            'grado' => $asignacion->seccion->curso->grado,
            'codigo' => 'I.M.2.2.1',
            'descripcion' => 'Mide objetos del entorno.',
            'grupo' => 2,
            'activo' => true,
        ]);
        $otraDestreza->indicadores()->attach($otroIndicador);
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->respuestaGemini((string) json_encode(['destreza' => "D{$destreza->id}", 'indicador' => "I{$otroIndicador->id}"])))
                ->push($this->respuestaGemini((string) json_encode(['destreza' => "D{$destreza->id}", 'indicador' => "I{$indicador->id}"])))
                ->push($this->respuestaGemini((string) json_encode($this->propuesta()))),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'duracion_minutos' => 45,
            'tema' => 'Sucesiones con tapas de colores',
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('seleccion.destreza_curricular_id', $destreza->id)
            ->assertJsonPath('seleccion.indicador_evaluacion_id', $indicador->id)
            ->assertJsonPath('propuesta.tema', 'Sucesiones con tapas de colores');
        Http::assertSentCount(3);
        Http::assertSent(function (Request $request) use ($destreza, $otraDestreza, $otroIndicador): bool {
            $datos = $request->data();
            $esquema = $datos['generationConfig']['responseJsonSchema'];
            $prompt = $datos['contents'][0]['parts'][0]['text'];

            return isset($esquema['properties']['destreza'])
                && $esquema['properties']['destreza']['enum'] === ["D{$destreza->id}", "D{$otraDestreza->id}"]
                && in_array("I{$otroIndicador->id}", $esquema['properties']['indicador']['enum'], true)
                && str_contains($prompt, 'Tema libre definido por el docente: Sucesiones con tapas de colores.')
                && str_contains($prompt, "Indicadores vinculados: I{$otroIndicador->id}");
        });
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->data()['contents'][0]['parts'][0]['text'],
            "Destreza seleccionada: {$destreza->codigo} {$destreza->descripcion}",
        ));
    }

    public function test_sin_tema_elige_la_destreza_a_partir_de_las_indicaciones(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->respuestaGemini((string) json_encode(['destreza' => "D{$destreza->id}", 'indicador' => "I{$indicador->id}"])))
                ->push($this->respuestaGemini((string) json_encode($this->propuesta()))),
        ]);

        $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'duracion_minutos' => 45,
            'indicaciones' => 'Clase sobre patrones con tapas de colores.',
        ])->assertOk()
            ->assertJsonPath('seleccion.destreza_curricular_id', $destreza->id)
            ->assertJsonPath('propuesta.tema', 'Patrones numéricos del entorno');
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->data()['contents'][0]['parts'][0]['text'],
            'deduce el contenido de la clase a partir de sus indicaciones',
        ));
    }

    public function test_sin_tema_ni_indicaciones_elige_la_destreza_segun_la_unidad(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push($this->respuestaGemini((string) json_encode(['destreza' => "D{$destreza->id}", 'indicador' => "I{$indicador->id}"])))
                ->push($this->respuestaGemini((string) json_encode($this->propuesta()))),
        ]);

        $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'duracion_minutos' => 45,
            'titulo_unidad' => 'Curiosidades matemáticas de las hormigas',
        ])->assertOk()
            ->assertJsonPath('seleccion.indicador_evaluacion_id', $indicador->id)
            ->assertJsonPath('propuesta.tema', 'Patrones numéricos del entorno');
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->data()['contents'][0]['parts'][0]['text'],
            'elige la destreza más pertinente para trabajar la unidad indicada',
        ));
    }

    public function test_sin_destreza_exige_un_catalogo_con_indicadores(): void
    {
        [$asignacion, $destreza, , $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();

        $destreza->indicadores()->detach();
        $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'duracion_minutos' => 45,
            'tema' => 'Sucesiones',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('destreza_curricular_id');
        Http::assertNothingSent();
    }

    public function test_rechaza_un_enfoque_o_contenido_que_no_pertenece_al_periodo(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        $datos = [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
        ];

        $this->actingAs($docente)
            ->postJson(route('planificaciones.asistente.periodos.store'), $datos + ['enfoque' => 'tema'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('enfoque');
        $this->postJson(route('planificaciones.asistente.periodos.store'), $datos + ['contenido_actual' => ['otro_campo' => 'x']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contenido_actual');
        Http::assertNothingSent();
    }

    public function test_rechaza_un_indicador_no_relacionado_sin_consultar_a_gemini(): void
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
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::response(
                $this->respuestaGemini((string) json_encode($propuesta)),
            ),
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

    public function test_corrige_saltos_de_linea_invalidos_en_la_respuesta_de_gemini(): void
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
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::response(
                $this->respuestaGemini((string) $contenido),
            ),
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
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::sequence()
                ->push($this->respuestaGemini('{respuesta truncada'))
                ->push($this->respuestaGemini((string) json_encode($this->propuesta()))),
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

            return $datos['generationConfig']['maxOutputTokens'] === 950
                && str_contains($datos['contents'][0]['parts'][0]['text'], 'REINTENTO');
        });
    }

    public function test_reintenta_cuando_una_fase_erca_es_demasiado_pobre(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $pobre = $this->propuesta();
        $pobre['experiencia'] = 'Observar una secuencia.';
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::sequence()
                ->push($this->respuestaGemini((string) json_encode($pobre)))
                ->push($this->respuestaGemini((string) json_encode($this->propuesta()))),
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
            $request->data()['contents'][0]['parts'][0]['text'],
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
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::response(
                $this->respuestaGemini((string) json_encode($propuesta)),
            ),
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

    public function test_muestra_un_error_util_cuando_gemini_no_esta_disponible(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::failedConnection('Gemini no disponible'),
        ]);

        $respuesta = $this->actingAs($docente)->postJson(route('planificaciones.asistente.periodos.store'), [
            'asignacion_id' => $asignacion->id,
            'destreza_curricular_id' => $destreza->id,
            'indicador_evaluacion_id' => $indicador->id,
            'duracion_minutos' => 45,
        ]);

        $respuesta->assertServiceUnavailable()
            ->assertJsonPath('message', 'No pudimos comunicarnos con Gemini. Verifica la conexión e inténtalo nuevamente.');
        Http::assertSentCount(1);
    }

    public function test_diferencia_un_timeout_de_gemini_de_una_desconexion(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent' => Http::failedConnection('Operation timed out after 60007 milliseconds'),
        ]);

        $this->actingAs($docente)
            ->postJson(route('planificaciones.asistente.periodos.store'), [
                'asignacion_id' => $asignacion->id,
                'destreza_curricular_id' => $destreza->id,
                'indicador_evaluacion_id' => $indicador->id,
                'duracion_minutos' => 45,
            ])
            ->assertStatus(504)
            ->assertJsonPath('message', 'La generación con Gemini superó el tiempo máximo. Inténtalo nuevamente con indicaciones más breves.');

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

    /** @return array{candidates: array<int, array{content: array{parts: array<int, array{text: string}>}}> } */
    private function respuestaGemini(string $contenido): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => $contenido]],
                ],
            ]],
        ];
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
