<?php

namespace Tests\Feature;

use App\Actions\ConstruirPeriodosPlanificacion;
use App\Models\Asignacion;
use App\Models\BloqueHorario;
use App\Models\DiaNoClase;
use App\Models\Horario;
use App\Models\PeriodoAcademico;
use App\Models\User;
use App\Services\CalendarioLectivo;
use App\Services\CalendarioPlanificaciones;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CronogramaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administracion_configura_trimestres_y_dias_sin_clases(): void
    {
        $periodo = PeriodoAcademico::factory()->create([
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2027-02-28',
        ]);
        $administrador = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($administrador)->put(route('cronogramas.update', $periodo), [
            'fecha_inicio' => '2026-05-04',
            'trimestre_1_fin' => '2026-08-14',
            'trimestre_2_fin' => '2026-11-27',
            'trimestre_3_fin' => '2027-02-26',
            'fecha_fin' => '2027-02-26',
        ])->assertSessionHasNoErrors();

        $cierres = $periodo->fresh()->cierresTrimestrales->keyBy('numero');
        $this->assertSame('2026-08-14', $cierres[1]->fecha_fin->format('Y-m-d'));
        $this->assertSame('2026-11-27', $cierres[2]->fecha_fin->format('Y-m-d'));
        $this->assertSame('2027-02-26', $cierres[3]->fecha_fin->format('Y-m-d'));

        $this->post(route('cronogramas.dias.store', $periodo), [
            'tipo_suspension' => 'rango',
            'fecha' => '2026-12-23',
            'fecha_fin' => '2027-01-03',
            'motivo' => 'Vacaciones de Navidad y fin de año',
        ])->assertSessionHasNoErrors();

        $dia = $periodo->fresh()->diasNoClase()->sole();
        $this->assertSame('2026-12-23', $dia->fecha->format('Y-m-d'));
        $this->assertSame('2027-01-03', $dia->fecha_fin->format('Y-m-d'));
        $this->assertSame('Vacaciones de Navidad y fin de año', $dia->motivo);
        $this->get(route('cronogramas.index', ['periodo_id' => $periodo->id]))
            ->assertOk()
            ->assertSee('Miércoles 23 de diciembre')
            ->assertSee('domingo 03 de enero de 2027');
    }

    public function test_docente_solo_puede_consultar_el_cronograma(): void
    {
        $asignacion = Asignacion::factory()->create();
        $periodo = $asignacion->seccion->periodo;
        $periodo->diasNoClase()->create(['fecha' => '2026-05-25', 'motivo' => 'Feriado local']);
        $docente = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);

        $this->actingAs($docente)->get(route('cronogramas.index', ['periodo_id' => $periodo->id]))
            ->assertOk()
            ->assertSee('Feriado local')
            ->assertSee('Lunes 25 de mayo de 2026')
            ->assertDontSee('Guardar cronograma')
            ->assertDontSee('Agregar día');

        $this->put(route('cronogramas.update', $periodo), [])->assertForbidden();
        $this->post(route('cronogramas.dias.store', $periodo), [])->assertForbidden();
    }

    public function test_administracion_puede_registrar_una_suspension_de_un_solo_dia(): void
    {
        $periodo = PeriodoAcademico::factory()->create();
        $administrador = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($administrador)->post(route('cronogramas.dias.store', $periodo), [
            'tipo_suspension' => 'dia',
            'fecha' => '2026-05-25',
            'fecha_fin' => '',
            'motivo' => 'Feriado local',
        ])->assertSessionHasNoErrors();

        $dia = $periodo->diasNoClase()->sole();
        $this->assertSame('2026-05-25', $dia->fecha->format('Y-m-d'));
        $this->assertNull($dia->fecha_fin);
    }

    public function test_un_dia_sin_clases_se_excluye_de_los_periodos_de_la_semana(): void
    {
        $periodo = PeriodoAcademico::factory()->create([
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2027-02-28',
        ]);
        $asignacion = Asignacion::factory()->create();
        $asignacion->seccion->update(['periodo_academico_id' => $periodo->id]);
        $horario = Horario::factory()->create([
            'docente_id' => $asignacion->docente_id,
            'seccion_id' => $asignacion->seccion_id,
            'periodo_academico_id' => $periodo->id,
        ]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 1]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 2]);
        BloqueHorario::factory()->create(['horario_id' => $horario->id, 'materia_id' => $asignacion->materia_id, 'dia_semana' => 3]);
        DiaNoClase::create(['periodo_academico_id' => $periodo->id, 'fecha' => '2026-06-01', 'fecha_fin' => '2026-06-02', 'motivo' => 'Suspensión de clases']);

        $periodos = app(ConstruirPeriodosPlanificacion::class)->handle($asignacion->fresh(), '2026-06-01');

        $this->assertCount(1, $periodos);
        $this->assertSame(3, $periodos->first()['dia_semana']);
        $this->assertSame('2026-06-03', $periodos->first()['fecha_clase']);

        $semana = app(CalendarioPlanificaciones::class)->semanas($asignacion->fresh())
            ->firstWhere('inicio', '2026-06-01');
        $this->assertNotNull($semana);
        $this->assertCount(1, $semana['bloques']);
        $this->assertSame('2026-06-03', $semana['bloques']->first()['fecha_clase']);
    }

    public function test_el_cronograma_resume_las_semanas_y_dias_lectivos_del_periodo(): void
    {
        $periodo = PeriodoAcademico::factory()->create([
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-07',
        ]);
        DiaNoClase::create([
            'periodo_academico_id' => $periodo->id,
            'fecha' => '2026-06-01',
            'fecha_fin' => '2026-06-02',
            'motivo' => 'Suspensión institucional',
        ]);

        $calendario = app(CalendarioLectivo::class)->construir($periodo);

        $this->assertCount(1, $calendario['semanas']);
        $this->assertSame(3, $calendario['total_dias_lectivos']);
        $this->assertSame(2, $calendario['total_dias_sin_clases']);
        $this->assertFalse($calendario['semanas']->first()['dias']->first()['es_lectivo']);
        $this->assertSame('Suspensión institucional', $calendario['semanas']->first()['dias']->first()['motivo']);
    }
}
