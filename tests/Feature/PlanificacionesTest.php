<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\BloqueHorario;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Planificacion;
use App\Models\Recreo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PlanificacionesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_docente_crea_borrador_y_administracion_lo_aprueba_en_dos_pasos(): void
    {
        $asignacion = Asignacion::factory()->create();
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);
        $administrador = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($docente);

        $this->get(route('planificaciones.create'))->assertSee('Objetivo disciplinar');
        $this->post(route('planificaciones.store'), $this->datos($asignacion))->assertSessionHasNoErrors();
        $planificacion = Planificacion::sole();
        $this->assertSame('borrador', $planificacion->estado);
        $this->get(route('planificaciones.show', $planificacion))
            ->assertSee('Plan microcurricular semanal')
            ->assertSee('Adaptaciones curriculares')
            ->assertSeeInOrder(['Estrategias metodológicas', 'Tema', 'Anticipación', 'Construcción', 'Consolidación']);
        $this->post(route('planificaciones.submit', $planificacion), ['version' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('planificaciones', ['id' => $planificacion->id, 'estado' => 'en_revision', 'version' => 2]);
        $this->get(route('planificaciones.edit', $planificacion))->assertForbidden();

        $this->actingAs($administrador);
        $this->post(route('planificaciones.approve', $planificacion), ['version' => 2])->assertForbidden();
        $this->post(route('planificaciones.review', $planificacion), ['version' => 2])->assertSessionHasNoErrors();
        $this->post(route('planificaciones.approve', $planificacion), ['version' => 3])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('planificaciones', ['id' => $planificacion->id, 'estado' => 'aprobada', 'revisado_por' => $administrador->id, 'aprobado_por' => $administrador->id]);
        $this->actingAs($docente)->put(route('planificaciones.update', $planificacion), $this->datos($asignacion, ['version' => 4]))->assertForbidden();
    }

    public function test_plan_incompleto_se_devuelve_y_reenvia_con_version_vigente(): void
    {
        $asignacion = Asignacion::factory()->create();
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);
        $administrador = User::factory()->create(['role' => 'SUPERADMIN']);
        $planificacion = Planificacion::factory()->create(['asignacion_id' => $asignacion->id, 'objetivo_disciplinar' => null]);
        $this->actingAs($docente);

        $this->post(route('planificaciones.submit', $planificacion), ['version' => 1])->assertSessionHasErrors('objetivo_disciplinar');
        $this->put(route('planificaciones.update', $planificacion), $this->datos($asignacion, ['version' => 1, 'numero_semana' => 1]))->assertSessionHasNoErrors();
        $this->post(route('planificaciones.submit', $planificacion), ['version' => 1])->assertSessionHasErrors('version');
        $this->post(route('planificaciones.submit', $planificacion), ['version' => 2])->assertSessionHasNoErrors();

        $this->actingAs($administrador);
        $this->post(route('planificaciones.devolver', $planificacion), ['version' => 3])->assertSessionHasErrors('observaciones_revision');
        $this->post(route('planificaciones.devolver', $planificacion), ['version' => 3, 'observaciones_revision' => 'Ajustar indicadores.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('planificaciones', ['id' => $planificacion->id, 'estado' => 'devuelta', 'observaciones_revision' => 'Ajustar indicadores.']);
        $this->actingAs($docente)->post(route('planificaciones.submit', $planificacion), ['version' => 4])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('planificaciones', ['id' => $planificacion->id, 'estado' => 'en_revision', 'observaciones_revision' => null]);
    }

    public function test_docente_no_puede_crear_ni_ver_planificaciones_ajenas(): void
    {
        $asignacion = Asignacion::factory()->create();
        $otra = Asignacion::factory()->create();
        $planificacion = Planificacion::factory()->create(['asignacion_id' => $asignacion->id]);
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $otra->docente_id]));

        $this->get(route('planificaciones.show', $planificacion))->assertForbidden();
        $this->post(route('planificaciones.store'), $this->datos($asignacion))->assertForbidden();
        $this->get(route('planificaciones.index'))->assertDontSee('Semana 1');

    }

    public function test_docente_navega_planificaciones_por_tarjetas_de_materias_asignadas(): void
    {
        $matematicas = Asignacion::factory()->create();
        $lengua = Asignacion::factory()->create(['docente_id' => $matematicas->docente_id]);
        $planMatematicas = Planificacion::factory()->create(['asignacion_id' => $matematicas->id, 'numero_semana' => 2]);
        $planLengua = Planificacion::factory()->create(['asignacion_id' => $lengua->id, 'numero_semana' => 3]);
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $matematicas->docente_id]);

        $portada = $this->actingAs($docente)->get(route('planificaciones.index'));

        $portada->assertOk()
            ->assertSee($matematicas->materia->nombre)
            ->assertSee($lengua->materia->nombre)
            ->assertSee('Ver planificaciones')
            ->assertSee(route('planificaciones.index', ['asignacion_id' => $matematicas->id]), false);

        $listado = $this->get(route('planificaciones.index', ['asignacion_id' => $matematicas->id]));

        $listado->assertOk()
            ->assertSee("Semana {$planMatematicas->numero_semana}")
            ->assertDontSee("Semana {$planLengua->numero_semana}")
            ->assertSee(route('planificaciones.create', ['asignacion_id' => $matematicas->id]), false);
    }

    public function test_nueva_planificacion_desde_materia_lleva_asignacion_fija_y_protege_ajenas(): void
    {
        $asignacion = Asignacion::factory()->create();
        $ajena = Asignacion::factory()->create();
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);

        $formulario = $this->actingAs($docente)->get(route('planificaciones.create', ['asignacion_id' => $asignacion->id]));

        $formulario->assertOk()
            ->assertSee($asignacion->materia->nombre)
            ->assertSee('name="asignacion_id" value="'.$asignacion->id.'"', false)
            ->assertDontSee('<select id="asignacion_id"', false);
        $this->get(route('planificaciones.index', ['asignacion_id' => $ajena->id]))->assertNotFound();
        $this->get(route('planificaciones.create', ['asignacion_id' => $ajena->id]))->assertNotFound();
    }

    public function test_agrupa_clases_consecutivas_en_un_bloque_y_separa_las_que_tienen_intervalo(): void
    {
        $asignacion = Asignacion::factory()->create();
        $horario = Horario::factory()->create([
            'docente_id' => $asignacion->docente_id,
            'seccion_id' => $asignacion->seccion_id,
            'periodo_academico_id' => $asignacion->seccion->periodo_academico_id,
        ]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1, 'hora_inicio' => '07:05', 'hora_fin' => '07:50']);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1, 'hora_inicio' => '07:50', 'hora_fin' => '08:35']);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1, 'hora_inicio' => '09:50', 'hora_fin' => '10:35']);
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);

        $formulario = $this->actingAs($docente)->get(route('planificaciones.create', ['asignacion_id' => $asignacion->id]));

        $formulario->assertOk()
            ->assertSee('Períodos 1 y 2')
            ->assertSee('(90 min)')
            ->assertSee('Período 3')
            ->assertSee('(45 min)');

        $datos = $this->datos($asignacion);
        $datos['periodos'] = array_slice($datos['periodos'], 0, 2);
        $this->post(route('planificaciones.store'), $datos)->assertSessionHasNoErrors();

        $planificacion = Planificacion::sole();
        $this->assertSame(3, $planificacion->numero_periodos);
        $this->assertDatabaseHas('periodos_planificacion', ['planificacion_id' => $planificacion->id, 'orden' => 1, 'numero_inicio' => 1, 'numero_fin' => 2, 'duracion_minutos' => 90]);
        $this->assertDatabaseHas('periodos_planificacion', ['planificacion_id' => $planificacion->id, 'orden' => 2, 'numero_inicio' => 3, 'numero_fin' => 3, 'duracion_minutos' => 45]);
    }

    public function test_recreo_no_divide_dos_periodos_consecutivos_de_la_misma_materia(): void
    {
        $asignacion = Asignacion::factory()->create();
        $horario = Horario::factory()->create([
            'docente_id' => $asignacion->docente_id,
            'seccion_id' => $asignacion->seccion_id,
            'periodo_academico_id' => $asignacion->seccion->periodo_academico_id,
        ]);
        Recreo::factory()->create([
            'periodo_academico_id' => $asignacion->seccion->periodo_academico_id,
            'subnivel' => $asignacion->seccion->curso->subnivel,
            'hora_inicio' => '09:20',
            'hora_fin' => '09:50',
            'dias_semana' => [1],
        ]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1, 'hora_inicio' => '08:35', 'hora_fin' => '09:20']);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1, 'hora_inicio' => '09:50', 'hora_fin' => '10:35']);
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);

        $formulario = $this->actingAs($docente)->get(route('planificaciones.create', ['asignacion_id' => $asignacion->id]));

        $formulario->assertOk()
            ->assertSee('Períodos 1 y 2')
            ->assertSee('(90 min)')
            ->assertSee('incluye recreo');
    }

    public function test_valida_semana_fechas_y_periodo_cerrado(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]));
        Planificacion::factory()->create(['asignacion_id' => $asignacion->id, 'numero_semana' => 4]);

        $this->post(route('planificaciones.store'), $this->datos($asignacion))->assertSessionHasErrors('numero_semana');
        $this->post(route('planificaciones.store'), $this->datos($asignacion, ['numero_semana' => 5, 'fecha_fin' => '2026-06-12']))->assertSessionHasErrors('fecha_fin');
        $this->post(route('planificaciones.store'), $this->datos($asignacion, ['numero_semana' => 5, 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-05']))->assertSessionHasErrors('fecha_inicio');
        $asignacion->seccion->periodo->update(['cerrado' => true]);
        $this->post(route('planificaciones.store'), $this->datos($asignacion, ['numero_semana' => 5]))->assertSessionHasErrors('asignacion_id');
        $this->assertDatabaseCount('planificaciones', 1);
    }

    public function test_asignacion_con_planificacion_conserva_docente_materia_y_curso(): void
    {
        $asignacion = Asignacion::factory()->create();
        Planificacion::factory()->create(['asignacion_id' => $asignacion->id]);
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']));

        $this->get(route('asignaciones.edit', $asignacion))->assertForbidden();
        $this->put(route('asignaciones.update', $asignacion), [
            'docente_id' => Docente::factory()->create()->id,
            'materia_id' => $asignacion->materia_id,
            'seccion_id' => $asignacion->seccion_id,
        ])->assertSessionHasErrors('seccion_id');
        $this->delete(route('asignaciones.destroy', $asignacion))->assertSessionHasErrors('registro');
        $this->assertModelExists($asignacion);
        $this->assertDatabaseHas('asignaciones', ['id' => $asignacion->id, 'docente_id' => $asignacion->docente_id]);
    }

    public function test_texto_del_docente_se_muestra_escapado(): void
    {
        $asignacion = Asignacion::factory()->create();
        $planificacion = Planificacion::factory()->create(['asignacion_id' => $asignacion->id, 'objetivo_disciplinar' => '<script>alert(1)</script>']);
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]));

        $this->get(route('planificaciones.show', $planificacion))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_periodo_cerrado_bloquea_revision_y_conserva_el_plan(): void
    {
        $planificacion = Planificacion::factory()->create(['estado' => 'en_revision']);
        $planificacion->asignacion->seccion->periodo->update(['cerrado' => true]);
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']));

        $this->get(route('planificaciones.show', $planificacion))->assertOk();
        $this->post(route('planificaciones.review', $planificacion), ['version' => 1])->assertForbidden();
        $this->assertDatabaseHas('planificaciones', ['id' => $planificacion->id, 'estado' => 'en_revision']);
    }

    private function datos(Asignacion $asignacion, array $cambios = []): array
    {
        if (! Horario::where('docente_id', $asignacion->docente_id)->where('seccion_id', $asignacion->seccion_id)->exists()) {
            $horario = Horario::factory()->create([
                'docente_id' => $asignacion->docente_id,
                'seccion_id' => $asignacion->seccion_id,
                'periodo_academico_id' => $asignacion->seccion->periodo_academico_id,
            ]);
            foreach (range(1, 3) as $dia) {
                BloqueHorario::factory()->create([
                    'horario_id' => $horario->id,
                    'materia_id' => $asignacion->materia_id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '07:00',
                    'hora_fin' => '07:45',
                ]);
            }
        }

        return array_replace([
            'asignacion_id' => $asignacion->id,
            'trimestre' => 1,
            'numero_semana' => 4,
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-05',
            'area' => 'Ciencias Sociales',
            'objetivo_disciplinar' => 'Objetivo de aprendizaje',
            'periodos' => array_fill(0, 3, [
                'destreza_desempeno' => 'Destreza con criterio de desempeño',
                'indicadores_logro' => 'Indicadores de logro',
                'recursos' => 'Recursos didácticos',
                'tema' => 'Tema de la clase',
                'anticipacion' => 'Anticipación del aprendizaje',
                'construccion' => 'Construcción del aprendizaje',
                'consolidacion' => 'Consolidación del aprendizaje',
                'tecnicas_evaluacion' => 'Observación y rúbrica',
            ]),
        ], $cambios);
    }
}
