<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PortalCursosTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_panel_muestra_solo_asignaciones_propias_de_periodos_abiertos(): void
    {
        $propia = Asignacion::factory()->create();
        $cerrada = Asignacion::factory()->create(['docente_id' => $propia->docente_id]);
        $cerrada->seccion->periodo->update(['cerrado' => true]);
        $ajena = Asignacion::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $propia->docente_id]));

        $this->get(route('dashboard'))->assertOk()->assertViewIs('PortalDocente.panel')
            ->assertSee($propia->materia->nombre)
            ->assertDontSee($cerrada->materia->nombre)
            ->assertDontSee($ajena->materia->nombre)
            ->assertSee(route('planificaciones.index', ['asignacion_id' => $propia->id]), false);
    }

    public function test_mis_materias_agrupa_cursos_y_enlaza_sus_planificaciones(): void
    {
        $primera = Asignacion::factory()->create();
        $segunda = Asignacion::factory()->create(['docente_id' => $primera->docente_id, 'materia_id' => $primera->materia_id]);
        $ajena = Asignacion::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $primera->docente_id]));

        $this->get(route('portal-docente.index'))->assertOk()
            ->assertSee($primera->materia->nombre)
            ->assertSee($primera->seccion->curso->nombre)
            ->assertSee($segunda->seccion->curso->nombre)
            ->assertDontSee($ajena->materia->nombre)
            ->assertSee(route('planificaciones.index', ['asignacion_id' => $primera->id]), false);
    }
}
