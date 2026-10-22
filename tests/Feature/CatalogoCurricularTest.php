<?php

namespace Tests\Feature;

use App\Models\DestrezaCurricular;
use App\Models\Docente;
use App\Models\IndicadorEvaluacion;
use App\Models\Materia;
use App\Models\User;
use App\Services\ImportarCatalogoCurricularExcel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CatalogoCurricularTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_solo_administracion_puede_gestionar_el_catalogo(): void
    {
        $this->get(route('catalogo-curricular.index'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'DOCENTE', 'docente_id' => Docente::factory()]))
            ->get(route('catalogo-curricular.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
            ->get(route('catalogo-curricular.index'))->assertOk()->assertSee('Importar desde Excel');
    }

    public function test_importa_los_grupos_y_relaciones_del_archivo_institucional(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Lengua y Literatura']);
        $resultado = app(ImportarCatalogoCurricularExcel::class)
            ->handle($materia, base_path('Lengua y literatura DCD y IC.xlsx'));

        $this->assertSame(69, $resultado['destrezas']);
        $this->assertSame(54, $resultado['indicadores']);
        $this->assertSame(138, $resultado['relaciones']);
        $this->assertGreaterThan(0, $resultado['competencias']);
        $this->assertDatabaseHas('destrezas_curriculares', ['materia_id' => $materia->id, 'grado' => 2, 'grupo' => 1, 'activo' => true]);
        $this->assertDatabaseHas('indicadores_evaluacion', ['materia_id' => $materia->id, 'grado' => 4, 'grupo' => 11, 'activo' => true]);

        $destreza = DestrezaCurricular::where('materia_id', $materia->id)->where('grado', 4)->where('grupo', 3)->firstOrFail();
        $this->assertCount(2, $destreza->indicadores);
        $this->assertTrue($destreza->indicadores->every(fn (IndicadorEvaluacion $indicador): bool => $indicador->grado === 4 && $indicador->grupo === 3));
    }

    public function test_admin_puede_subir_el_excel_y_consultar_un_grado(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Lengua y Literatura']);
        $archivo = UploadedFile::fake()->createWithContent('catalogo.xlsx', file_get_contents(base_path('Lengua y literatura DCD y IC.xlsx')));

        $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
            ->post(route('catalogo-curricular.importar'), ['materia_id' => $materia->id, 'archivo' => $archivo])
            ->assertSessionHasNoErrors()->assertRedirect(route('catalogo-curricular.index', ['materia_id' => $materia->id, 'grado' => 4]));

        $this->get(route('catalogo-curricular.index', ['materia_id' => $materia->id, 'grado' => 2]))
            ->assertOk()->assertSee('Grupo 1')->assertSee('2.º grado');
    }

    public function test_importa_matematica_y_conserva_varias_competencias_por_destreza(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Matemática']);
        $resultado = app(ImportarCatalogoCurricularExcel::class)
            ->handle($materia, base_path('Matematicas_destrezas_e_indicadores_corregidos.xlsx'));

        $this->assertSame(120, $resultado['destrezas']);
        $this->assertSame(54, $resultado['indicadores']);
        $destreza = DestrezaCurricular::with('competencias')->where('materia_id', $materia->id)
            ->where('grado', 4)->where('codigo', 'M.2.1.24')->sole();
        $this->assertEqualsCanonicalizing(['CM', 'C', 'CS', 'EV', 'ES'], $destreza->competencias->pluck('codigo')->all());
    }
}
