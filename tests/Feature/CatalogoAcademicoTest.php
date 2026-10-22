<?php

namespace Tests\Feature;

use App\Livewire\Academico\Catalogo;
use App\Models\Curso;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoAcademicoTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    }

    public function test_catalog_requires_admin(): void
    {
        $this->get('/academico')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/academico')->assertForbidden();
        Livewire::test(Catalogo::class)->assertForbidden();
    }

    public function test_creates_course_period_and_section(): void
    {
        $this->admin();
        $this->get('/academico')->assertOk()->assertSee('Cursos y períodos');
        $component = Livewire::test(Catalogo::class)->call('create')
            ->set('formulario.nombre', 'Octavo de básica')->set('formulario.grado', 8)->set('formulario.subnivel', 'basica_superior')->set('formulario.orden', 1)->call('save')->assertHasNoErrors()
            ->call('tab', 'periodos')->call('create')->set('formulario.nombre', '2026–2027')
            ->set('formulario.fecha_inicio', '2026-04-01')->set('formulario.fecha_fin', '2027-03-01')->call('save')->assertHasNoErrors();
        $component->call('tab', 'secciones')->call('create')->set('formulario.nombre', 'A')
            ->set('formulario.curso_id', Curso::sole()->id)->set('formulario.periodo_academico_id', PeriodoAcademico::sole()->id)
            ->call('save')->assertHasNoErrors()->assertSee('Octavo de básica');
        $this->assertDatabaseHas('secciones', ['nombre' => 'A', 'curso_id' => Curso::sole()->id, 'periodo_academico_id' => PeriodoAcademico::sole()->id]);
    }

    public function test_unreferenced_courses_can_be_edited_and_deleted_after_confirmation(): void
    {
        $this->admin();
        $curso = Curso::factory()->create(['orden' => 1]);
        $component = Livewire::test(Catalogo::class)->call('edit', $curso->id)->set('formulario.nombre', 'Curso corregido')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Curso corregido', $curso->fresh()->nombre);
        $component->call('confirmarEliminacion', $curso->id);
        $this->assertModelExists($curso);
        $component->call('delete')->assertHasNoErrors();
        $this->assertModelMissing($curso);
    }

    public function test_duplicate_and_invalid_values_are_rejected(): void
    {
        $this->admin();
        Curso::factory()->create(['nombre' => 'Octavo', 'orden' => 1]);
        Livewire::test(Catalogo::class)->call('create')->call('save')->assertHasErrors(['formulario.nombre', 'formulario.orden'])
            ->set('formulario.nombre', 'Octavo')->set('formulario.orden', 1)->call('save')
            ->assertHasErrors(['formulario.nombre' => 'unique', 'formulario.orden' => 'unique']);
        $this->assertDatabaseCount('cursos', 1);
    }

    public function test_periods_cannot_overlap_or_end_before_start(): void
    {
        $this->admin();
        PeriodoAcademico::factory()->create();
        Livewire::test(Catalogo::class)->call('tab', 'periodos')->call('create')->set('formulario.nombre', 'Duplicado')
            ->set('formulario.fecha_inicio', '2026-06-01')->set('formulario.fecha_fin', '2026-01-01')->call('save')->assertHasErrors('formulario.fecha_fin')
            ->set('formulario.fecha_fin', '2027-06-01')->call('save')->assertHasErrors('catalogo');
        $this->assertDatabaseCount('periodos_academicos', 1);
    }

    public function test_section_names_are_unique_within_course_and_period(): void
    {
        $this->admin();
        $seccion = Seccion::factory()->create();
        Livewire::test(Catalogo::class)->call('tab', 'secciones')->call('create')->set('formulario.nombre', 'A')
            ->set('formulario.curso_id', $seccion->curso_id)->set('formulario.periodo_academico_id', $seccion->periodo_academico_id)
            ->call('save')->assertHasErrors(['formulario.nombre' => 'unique'])
            ->set('formulario.nombre', 'B')->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('secciones', 2);
    }

    public function test_referenced_course_cannot_be_deleted_or_reordered(): void
    {
        $this->admin();
        $seccion = Seccion::factory()->create();
        Livewire::test(Catalogo::class)->call('confirmarEliminacion', $seccion->curso_id)->assertHasErrors('catalogo')
            ->call('edit', $seccion->curso_id)->set('formulario.nombre', 'Cambio')->call('save')->assertHasErrors('catalogo');
        $this->assertNotSame('Cambio', $seccion->curso->fresh()->nombre);
    }

    public function test_period_can_be_closed_from_the_catalog(): void
    {
        $this->admin();
        $seccion = Seccion::factory()->create();

        Livewire::test(Catalogo::class)->call('tab', 'periodos')
            ->call('cerrarPeriodo', $seccion->periodo_academico_id)->assertHasNoErrors();

        $this->assertTrue($seccion->periodo->fresh()->cerrado);
    }

    public function test_final_level_must_be_last_and_unique(): void
    {
        $this->admin();
        Curso::factory()->create(['orden' => 3, 'es_final' => true]);
        Livewire::test(Catalogo::class)->call('create')->set('formulario.nombre', 'Otro')->set('formulario.grado', 10)->set('formulario.subnivel', 'bachillerato')->set('formulario.orden', 4)
            ->call('save')->assertHasErrors('catalogo')->set('formulario.orden', 2)->set('formulario.es_final', true)
            ->call('save')->assertHasErrors('catalogo');
        $this->assertDatabaseCount('cursos', 1);
    }
}
