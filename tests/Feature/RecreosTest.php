<?php

namespace Tests\Feature;

use App\Models\BloqueHorario;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Recreo;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecreosTest extends TestCase
{
    use RefreshDatabase;

    public function test_administracion_configura_recreo_por_periodo_y_subnivel(): void
    {
        $administrador = User::factory()->create(['role' => 'ADMIN']);
        $seccion = Seccion::factory()->for(Curso::factory()->create(['subnivel' => 'basica_elemental']))->create();

        $respuesta = $this->actingAs($administrador)->post(route('recreos.store'), [
            'periodo_academico_id' => $seccion->periodo_academico_id,
            'subnivel' => 'basica_elemental',
            'nombre' => 'Recreo',
            'hora_inicio' => '09:20',
            'hora_fin' => '09:50',
            'dias_semana' => [1, 2, 3, 4, 5],
            'activo' => '1',
        ]);

        $respuesta->assertRedirectToRoute('recreos.index')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('recreos', [
            'periodo_academico_id' => $seccion->periodo_academico_id,
            'subnivel' => 'basica_elemental',
            'hora_inicio' => '09:20',
            'hora_fin' => '09:50',
            'activo' => true,
        ]);
    }

    public function test_horario_muestra_recreo_sin_contarlo_como_periodo_de_clase(): void
    {
        $curso = Curso::factory()->create(['nombre' => 'Segundo', 'subnivel' => 'basica_elemental']);
        $seccion = Seccion::factory()->for($curso)->create(['nombre' => 'A']);
        $horario = Horario::factory()->create(['seccion_id' => $seccion->id, 'periodo_academico_id' => $seccion->periodo_academico_id]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'dia_semana' => 1, 'hora_inicio' => '08:35', 'hora_fin' => '09:20']);
        Recreo::factory()->create(['periodo_academico_id' => $seccion->periodo_academico_id, 'subnivel' => 'basica_elemental']);
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $horario->docente_id]);

        $respuesta = $this->actingAs($docente)->get(route('horarios.show', $horario));

        $respuesta->assertOk()->assertSee('Recreo')->assertSee('09:20')->assertSee('09:50')->assertSeeInOrder(['Períodos semanales', '>1<'], false);
    }

    public function test_no_permite_configurar_recreo_sobre_una_clase_existente(): void
    {
        $administrador = User::factory()->create(['role' => 'ADMIN']);
        $curso = Curso::factory()->create(['subnivel' => 'basica_elemental']);
        $seccion = Seccion::factory()->for($curso)->create();
        $horario = Horario::factory()->create(['seccion_id' => $seccion->id, 'periodo_academico_id' => $seccion->periodo_academico_id]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'dia_semana' => 1, 'hora_inicio' => '09:30', 'hora_fin' => '10:10']);

        $respuesta = $this->actingAs($administrador)->post(route('recreos.store'), [
            'periodo_academico_id' => $seccion->periodo_academico_id,
            'subnivel' => 'basica_elemental',
            'nombre' => 'Recreo',
            'hora_inicio' => '09:20',
            'hora_fin' => '09:50',
            'dias_semana' => [1, 2, 3, 4, 5],
            'activo' => '1',
        ]);

        $respuesta->assertSessionHasErrors('hora_inicio');
        $this->assertDatabaseCount('recreos', 0);
    }

    public function test_no_permite_clase_durante_recreo_institucional(): void
    {
        $administrador = User::factory()->create(['role' => 'ADMIN']);
        $curso = Curso::factory()->create(['subnivel' => 'basica_elemental']);
        $seccion = Seccion::factory()->for($curso)->create();
        $materia = Materia::factory()->create();
        Recreo::factory()->create(['periodo_academico_id' => $seccion->periodo_academico_id, 'subnivel' => 'basica_elemental']);
        $docente = Docente::factory()->create();

        $respuesta = $this->actingAs($administrador)->post(route('horarios.store'), [
            'docente_id' => $docente->id,
            'seccion_id' => $seccion->id,
            'bloques' => [[
                'materia_id' => $materia->id,
                'dia_semana' => 1,
                'hora_inicio' => '09:30',
                'hora_fin' => '10:10',
            ]],
        ]);

        $respuesta->assertSessionHasErrors('bloques.0.hora_inicio');
        $this->assertDatabaseCount('horarios', 0);
    }

    public function test_docente_no_administra_recreos(): void
    {
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => Docente::factory()->create()->id]);

        $this->actingAs($docente)->get(route('recreos.index'))->assertForbidden();
    }
}
