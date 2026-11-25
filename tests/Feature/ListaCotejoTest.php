<?php

namespace Tests\Feature;

use App\Models\PeriodoPlanificacion;
use App\Models\Planificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ListaCotejoTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const INSTRUMENTO = "Lista de cotejo: Multiplicación\nEscala: Logrado / No logrado\nCriterios:\n1. Representa 4 x 3 = 12 con tapas\n2. Resuelve el problema de las macetas";

    public function test_docente_descarga_la_lista_de_cotejo_de_un_bloque_en_pdf(): void
    {
        $periodo = $this->periodoConInstrumento(self::INSTRUMENTO);
        $planificacion = $periodo->planificacion;

        $this->actingAs($this->docenteDe($planificacion))
            ->get(route('planificaciones.show', $planificacion))
            ->assertSee(route('planificaciones.lista-cotejo', [$planificacion, $periodo]), false)
            ->assertSee('Descargar lista de cotejo (PDF)');

        $respuesta = $this->get(route('planificaciones.lista-cotejo', [$planificacion, $periodo]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString('attachment', $respuesta->headers->get('content-disposition'));
        $this->assertStringContainsString('lista-de-cotejo', $respuesta->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_otro_docente_no_puede_descargar_la_lista_de_cotejo(): void
    {
        $periodo = $this->periodoConInstrumento(self::INSTRUMENTO);
        $ajeno = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => Planificacion::factory()->create()->asignacion->docente_id]);

        $this->actingAs($ajeno)
            ->get(route('planificaciones.lista-cotejo', [$periodo->planificacion, $periodo]))
            ->assertForbidden();
    }

    public function test_no_acepta_un_bloque_de_otra_planificacion(): void
    {
        $periodo = $this->periodoConInstrumento(self::INSTRUMENTO);
        $otraPlanificacion = Planificacion::factory()->create(['asignacion_id' => $periodo->planificacion->asignacion_id, 'numero_semana' => 2]);

        $this->actingAs($this->docenteDe($periodo->planificacion))
            ->get(route('planificaciones.lista-cotejo', [$otraPlanificacion, $periodo]))
            ->assertNotFound();
    }

    public function test_no_genera_pdf_si_el_instrumento_no_tiene_criterios_numerados(): void
    {
        $periodo = $this->periodoConInstrumento('Lista de cotejo con criterios de logro.');

        $this->actingAs($this->docenteDe($periodo->planificacion))
            ->get(route('planificaciones.lista-cotejo', [$periodo->planificacion, $periodo]))
            ->assertNotFound();
    }

    private function periodoConInstrumento(string $instrumento): PeriodoPlanificacion
    {
        $periodo = Planificacion::factory()->create()->periodos()->firstOrFail();
        $periodo->update(['instrumento_evaluacion' => $instrumento]);

        return $periodo;
    }

    private function docenteDe(Planificacion $planificacion): User
    {
        return User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $planificacion->asignacion->docente_id]);
    }
}
