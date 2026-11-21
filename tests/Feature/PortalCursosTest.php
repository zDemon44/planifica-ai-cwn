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

    public function test_la_antigua_ruta_mis_materias_redirige_a_planificaciones(): void
    {
        $asignacion = Asignacion::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]));

        $this->get('/mis-cursos')->assertMovedPermanently()->assertRedirect('/planificaciones');
        $this->get(route('dashboard'))->assertOk()
            ->assertDontSee('Mis materias')
            ->assertSee(route('planificaciones.index'), false);
    }
}
