<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TraduccionAcademicaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renombrado_conserva_cursos_periodos_y_secciones(): void
    {
        $curso = Curso::factory()->create();
        $periodo = PeriodoAcademico::factory()->create();
        $seccion = Seccion::factory()->create(['curso_id' => $curso->id, 'periodo_academico_id' => $periodo->id]);
        $migracion = require database_path('migrations/2026_09_12_054807_traducir_estructura_academica_al_espanol.php');

        $migracion->down();

        $this->assertDatabaseHas('courses', ['id' => $curso->id, 'name' => $curso->nombre]);
        $this->assertDatabaseHas('academic_periods', ['id' => $periodo->id, 'name' => $periodo->nombre]);
        $this->assertDatabaseHas('sections', ['id' => $seccion->id, 'course_id' => $curso->id]);

        $migracion->up();

        $this->assertDatabaseHas('cursos', ['id' => $curso->id, 'nombre' => $curso->nombre]);
        $this->assertDatabaseHas('periodos_academicos', ['id' => $periodo->id, 'nombre' => $periodo->nombre]);
        $this->assertSame($curso->id, $seccion->fresh()->curso->id);
        $this->assertSame($periodo->id, $seccion->fresh()->periodo->id);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }
}
