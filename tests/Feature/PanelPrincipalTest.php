<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\BloqueHorario;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Planificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PanelPrincipalTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-18 09:00:00');
    }

    public function test_administracion_ve_bandeja_de_revision_y_conteos_por_estado(): void
    {
        $pendiente = Planificacion::factory()->create(['estado' => 'en_revision', 'numero_semana' => 7, 'enviado_en' => now()->subDay()]);
        Planificacion::factory()->create(['estado' => 'aprobada', 'asignacion_id' => $pendiente->asignacion_id, 'numero_semana' => 8]);
        Planificacion::factory()->create(['estado' => 'borrador', 'asignacion_id' => $pendiente->asignacion_id, 'numero_semana' => 9]);
        Docente::factory()->create(['estado' => 'activo']);
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']));

        $this->get(route('dashboard'))->assertOk()->assertViewIs('panel')
            ->assertViewHas('estados', fn ($estados): bool => $estados['en_revision'] === 1 && $estados['aprobada'] === 1 && $estados['borrador'] === 1)
            ->assertSee(route('planificaciones.show', $pendiente), false)
            ->assertSee($pendiente->asignacion->materia->nombre)
            ->assertSee('docentes activos sin cuenta')
            ->assertDontSee(route('usuarios.index'), false);
    }

    public function test_bandeja_vacia_muestra_mensaje_y_superadmin_ve_acceso_a_usuarios(): void
    {
        Planificacion::factory()->create(['estado' => 'aprobada']);
        $this->actingAs(User::factory()->create(['role' => 'SUPERADMIN']));

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Todo al día')
            ->assertSee(route('usuarios.index'), false);
    }

    public function test_docente_ve_su_semana_y_planificaciones_devueltas(): void
    {
        $sinPlanificar = Asignacion::factory()->create();
        $planificada = Asignacion::factory()->create(['docente_id' => $sinPlanificar->docente_id]);
        foreach ([$sinPlanificar, $planificada] as $asignacion) {
            $horario = Horario::factory()->create(['docente_id' => $asignacion->docente_id, 'seccion_id' => $asignacion->seccion_id]);
            BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 2]);
        }
        Planificacion::factory()->create(['asignacion_id' => $planificada->id, 'estado' => 'en_revision', 'numero_semana' => 12, 'fecha_inicio' => '2026-11-16', 'fecha_fin' => '2026-11-20']);
        Planificacion::factory()->create(['asignacion_id' => $planificada->id, 'estado' => 'devuelta', 'numero_semana' => 10, 'observaciones_revision' => 'Completar la fase de aplicación.']);
        $ajena = Planificacion::factory()->create(['estado' => 'devuelta', 'observaciones_revision' => 'Observación de otro docente.']);
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $sinPlanificar->docente_id]));

        $this->get(route('dashboard'))->assertOk()->assertViewIs('PortalDocente.panel')
            ->assertSee('Tu semana')
            ->assertSee('1 de 2 planificadas')
            ->assertSee(route('planificaciones.create', ['asignacion_id' => $sinPlanificar->id, 'fecha_inicio' => '2026-11-16']))
            ->assertSee('Tienes 1 planificación devuelta')
            ->assertSee('Completar la fase de aplicación.')
            ->assertDontSee($ajena->observaciones_revision);
    }

    public function test_en_fin_de_semana_el_docente_ve_la_proxima_semana(): void
    {
        Carbon::setTestNow('2026-11-21 10:00:00');
        $asignacion = Asignacion::factory()->create();
        $horario = Horario::factory()->create(['docente_id' => $asignacion->docente_id, 'seccion_id' => $asignacion->seccion_id]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1]);
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]));

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Próxima semana')
            ->assertSee(route('planificaciones.create', ['asignacion_id' => $asignacion->id, 'fecha_inicio' => '2026-11-23']));
    }
}
