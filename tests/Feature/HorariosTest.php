<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_administracion_crea_horario_y_asigna_todas_sus_materias_al_docente_del_curso(): void
    {
        $administrador = User::factory()->create(['role' => 'ADMIN']);
        $docente = Docente::factory()->create();
        $otroDocente = Docente::factory()->create();
        $seccion = Seccion::factory()->create();
        $matematica = Materia::factory()->create();
        $lengua = Materia::factory()->create();
        Asignacion::factory()->create(['docente_id' => $otroDocente->id, 'materia_id' => $matematica->id, 'seccion_id' => $seccion->id]);

        $respuesta = $this->actingAs($administrador)->post(route('horarios.store'), $this->datos($docente, $seccion, $matematica, $lengua));

        $horario = Horario::sole();
        $respuesta->assertRedirect(route('horarios.show', $horario))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bloque_horarios', 2);
        $this->assertDatabaseHas('asignaciones', ['docente_id' => $docente->id, 'materia_id' => $matematica->id, 'seccion_id' => $seccion->id]);
        $this->assertDatabaseHas('asignaciones', ['docente_id' => $docente->id, 'materia_id' => $lengua->id, 'seccion_id' => $seccion->id]);
        $this->assertDatabaseMissing('asignaciones', ['docente_id' => $otroDocente->id, 'materia_id' => $matematica->id, 'seccion_id' => $seccion->id]);
    }

    public function test_docente_solo_consulta_su_horario(): void
    {
        $propio = Horario::factory()->create();
        $ajeno = Horario::factory()->create();
        $usuario = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $propio->docente_id]);

        $this->actingAs($usuario)->get(route('horarios.index'))->assertRedirectToRoute('horarios.show', $propio);
        $this->get(route('horarios.show', $propio))->assertOk()->assertSee($propio->seccion->curso->nombre);
        $this->get(route('horarios.show', $ajeno))->assertForbidden();
        $this->get(route('horarios.create'))->assertForbidden();
    }

    public function test_no_permite_bloques_cruzados_ni_dos_cursos_para_el_mismo_docente(): void
    {
        $administrador = User::factory()->create(['role' => 'ADMIN']);
        $docente = Docente::factory()->create();
        $seccion = Seccion::factory()->create();
        $materia = Materia::factory()->create();
        $datos = $this->datos($docente, $seccion, $materia, $materia);
        $datos['bloques'][1] = ['materia_id' => $materia->id, 'dia_semana' => 1, 'hora_inicio' => '07:20', 'hora_fin' => '08:00'];

        $this->actingAs($administrador)->post(route('horarios.store'), $datos)->assertSessionHasErrors('bloques.1.hora_inicio');
        $this->post(route('horarios.store'), $this->datos($docente, $seccion, $materia, $materia))->assertSessionHasNoErrors();

        $otraSeccion = Seccion::factory()->create(['periodo_academico_id' => $seccion->periodo_academico_id]);
        $this->post(route('horarios.store'), $this->datos($docente, $otraSeccion, $materia, $materia))->assertSessionHasErrors('docente_id');
        $this->assertDatabaseCount('horarios', 1);
    }

    /** @return array<string, mixed> */
    private function datos(Docente $docente, Seccion $seccion, Materia $primera, Materia $segunda): array
    {
        return [
            'docente_id' => $docente->id,
            'seccion_id' => $seccion->id,
            'bloques' => [
                ['materia_id' => $primera->id, 'dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '07:40'],
                ['materia_id' => $segunda->id, 'dia_semana' => 2, 'hora_inicio' => '07:00', 'hora_fin' => '07:40'],
            ],
        ];
    }
}
