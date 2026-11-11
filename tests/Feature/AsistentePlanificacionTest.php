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

    public function test_docente_genera_un_periodo_estructurado_desde_su_destreza(): void
    {
        [$asignacion, $destreza, $indicador, $docente] = $this->contextoCurricular();
        $propuesta = $this->propuesta();
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
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('message', 'Propuesta generada. Revísala antes de guardar el borrador.')
            ->assertJsonPath('propuesta.tema', 'Patrones numéricos del entorno')
            ->assertJsonPath('propuesta.dua_aplicacion', 'Expresión mediante material concreto o dibujo.');
        Http::assertSent(function (Request $request) use ($asignacion, $destreza): bool {
            $datos = $request->data();

            return $request->url() === 'http://127.0.0.1:11434/api/chat'
                && $datos['model'] === 'qwen2.5:3b'
                && $datos['stream'] === false
                && $datos['format']['type'] === 'object'
                && $datos['options']['num_predict'] === 1400
                && str_contains($datos['messages'][1]['content'], $asignacion->materia->nombre)
                && str_contains($datos['messages'][1]['content'], $destreza->descripcion)
                && str_contains($datos['messages'][1]['content'], '90 minutos');
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

            return $datos['options']['num_predict'] === 1800
                && str_contains($datos['messages'][1]['content'], 'REINTENTO');
        });
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
            'recursos' => 'Tapas de colores, tarjetas y pizarra.',
            'experiencia' => 'Observar y continuar secuencias construidas con tapas.',
            'dua_experiencia' => 'Participación visual y manipulativa.',
            'reflexion' => 'Explicar qué elemento cambia en cada secuencia.',
            'dua_reflexion' => 'Respuesta oral, escrita o señalada.',
            'conceptualizacion' => 'Reconocer la regla que forma un patrón numérico.',
            'dua_conceptualizacion' => 'Representación con símbolos, colores y ejemplos.',
            'aplicacion' => 'Crear y compartir un patrón numérico propio.',
            'dua_aplicacion' => 'Expresión mediante material concreto o dibujo.',
            'tecnica_evaluacion' => 'Observación directa.',
            'instrumento_evaluacion' => 'Lista de cotejo.',
        ];
    }
}
