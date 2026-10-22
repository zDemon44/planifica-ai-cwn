<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\DestrezaCurricular;
use App\Models\Docente;
use App\Models\IndicadorEvaluacion;
use App\Models\Materia;
use App\Models\Planificacion;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SeguimientoCurricularTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_muestra_en_verde_y_cuenta_el_uso_para_docente_y_administracion(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Lengua y Literatura']);
        $curso = Curso::factory()->create(['nombre' => 'Cuarto', 'grado' => 4]);
        $docente = Docente::factory()->create();
        $otraDocente = Docente::factory()->create();
        $seccion = Seccion::factory()->create(['curso_id' => $curso->id, 'nombre' => 'A']);
        $otraSeccion = Seccion::factory()->create(['curso_id' => $curso->id, 'periodo_academico_id' => $seccion->periodo_academico_id, 'nombre' => 'B']);
        $asignacion = Asignacion::factory()->create(['materia_id' => $materia->id, 'docente_id' => $docente->id, 'seccion_id' => $seccion->id]);
        $otraAsignacion = Asignacion::factory()->create(['materia_id' => $materia->id, 'docente_id' => $otraDocente->id, 'seccion_id' => $otraSeccion->id]);
        $destreza = DestrezaCurricular::create(['materia_id' => $materia->id, 'grado' => 4, 'codigo' => 'LL.2.1.1', 'descripcion' => 'Destreza de prueba', 'grupo' => 1]);
        $indicador = IndicadorEvaluacion::create(['materia_id' => $materia->id, 'grado' => 4, 'codigo' => 'I.LL.2.1.1', 'descripcion' => 'Indicador de prueba', 'grupo' => 1]);
        $destreza->indicadores()->attach($indicador);

        foreach ([1, 2] as $semana) {
            $planificacion = Planificacion::factory()->create(['asignacion_id' => $asignacion->id, 'numero_semana' => $semana, 'numero_periodos' => 1]);
            $planificacion->periodos()->first()->update(['destreza_curricular_id' => $destreza->id, 'indicador_evaluacion_id' => $indicador->id]);
        }
        $planificacionAjena = Planificacion::factory()->create(['asignacion_id' => $otraAsignacion->id, 'numero_periodos' => 1]);
        $planificacionAjena->periodos()->first()->update(['destreza_curricular_id' => $destreza->id, 'indicador_evaluacion_id' => $indicador->id]);

        $usuarioDocente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $docente->id]);
        $this->actingAs($usuarioDocente)->get(route('seguimiento-curricular.index', ['materia_id' => $materia->id, 'grado' => 4]))
            ->assertOk()->assertSee('Destreza de prueba')->assertSee('Indicador de prueba')
            ->assertSee('2 planif.')->assertSee('is-used', false);

        $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
            ->get(route('seguimiento-curricular.index', ['materia_id' => $materia->id, 'grado' => 4]))
            ->assertOk()->assertSee('3 planif.');
    }

    public function test_requiere_autenticacion_y_docente_no_accede_a_materia_no_asignada(): void
    {
        $materia = Materia::factory()->create();
        DestrezaCurricular::create(['materia_id' => $materia->id, 'grado' => 4, 'codigo' => 'LL.2.1.1', 'descripcion' => 'Destreza', 'grupo' => 1]);
        $docente = Docente::factory()->create();

        $this->get(route('seguimiento-curricular.index'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $docente->id]))
            ->get(route('seguimiento-curricular.index', ['materia_id' => $materia->id, 'grado' => 4]))->assertNotFound();
    }
}
