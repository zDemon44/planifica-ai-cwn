<?php

namespace Tests\Feature;

use App\Models\Curriculo;
use App\Models\LibroTexto;
use App\Models\Materia;
use App\Models\ObjetivoCurricular;
use App\Models\TemaLibro;
use App\Services\ImportarCatalogoCurricularExcel;
use App\Services\ImportarLibroTextoExcel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ImportarLibroTextoExcelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_importa_unidades_objetivos_y_temas_sin_inventar_catalogo_faltante(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Matemática']);
        $this->crearObjetivosDelLibro($materia);
        $importadorCatalogo = app(ImportarCatalogoCurricularExcel::class);
        $importadorCatalogo->handle(
            $materia,
            Curriculo::where('codigo', Curriculo::CODIGO_VIGENTE)->sole(),
            base_path('Matematicas_destrezas_e_indicadores_corregidos.xlsx'),
        );
        $importadorCatalogo->handle(
            $materia,
            Curriculo::where('codigo', Curriculo::CODIGO_ANTERIOR)->sole(),
            base_path('outputs/curriculo-faltantes-desagregados/Matematicas_destrezas_e_indicadores_Faltantes_desagregados.xlsx'),
        );

        $resultado = app(ImportarLibroTextoExcel::class)->handle(
            $materia,
            base_path('Datos libro matematicas 4to.xlsx'),
            'matematica-4to',
            'Libro de Matemática 4.º EGB',
            4,
        );

        $this->assertSame(6, $resultado['unidades']);
        $this->assertSame(12, $resultado['objetivos']);
        $this->assertSame(76, $resultado['temas']);
        $this->assertSame(64, $resultado['temas_completos']);
        $this->assertSame(12, $resultado['temas_pendientes']);
        $this->assertSame(66, $resultado['destrezas_vinculadas']);
        $this->assertSame(65, $resultado['indicadores_vinculados']);

        $libro = LibroTexto::with(['unidades.objetivos', 'unidades.temas.indicadorEvaluacion.curriculo'])->sole();
        $this->assertSame('matematica-4to', $libro->codigo);
        $this->assertCount(6, $libro->unidades);
        $this->assertSame(12, $libro->unidades->sum(fn ($unidad): int => $unidad->objetivos->count()));

        $temaConIndicadorAmbiguo = TemaLibro::query()
            ->where('codigo_destreza_fuente', 'M.2.1.23')
            ->where('codigo_indicador_fuente', 'I.M.2.2.3')
            ->with('indicadorEvaluacion.curriculo')
            ->sole();
        $this->assertSame('I.M.2.2.3', $temaConIndicadorAmbiguo->indicadorEvaluacion->codigo);
        $this->assertSame(Curriculo::CODIGO_ANTERIOR, $temaConIndicadorAmbiguo->indicadorEvaluacion->curriculo->codigo);

        $temaPendiente = TemaLibro::where('codigo_destreza_fuente', 'M.2.1.3')->sole();
        $this->assertNull($temaPendiente->destreza_curricular_id);
        $this->assertNull($temaPendiente->indicador_evaluacion_id);
    }

    public function test_reimportar_el_mismo_excel_actualiza_sin_duplicar_registros(): void
    {
        $materia = Materia::factory()->create(['nombre' => 'Matemática']);
        $this->crearObjetivosDelLibro($materia);
        $importador = app(ImportarLibroTextoExcel::class);
        $argumentos = [
            $materia,
            base_path('Datos libro matematicas 4to.xlsx'),
            'matematica-4to',
            'Libro de Matemática 4.º EGB',
            4,
        ];

        $importador->handle(...$argumentos);
        $importador->handle(...$argumentos);

        $this->assertDatabaseCount('libros_texto', 1);
        $this->assertDatabaseCount('unidades_libro', 6);
        $this->assertDatabaseCount('objetivo_unidad_libro', 12);
        $this->assertDatabaseCount('temas_libro', 76);
    }

    private function crearObjetivosDelLibro(Materia $materia): void
    {
        foreach (['O.M.2.1.', 'O.M.2.3.', 'O.M.2.4.', 'O.M.2.5.', 'O.M.2.6.', 'O.M.2.7.'] as $codigo) {
            ObjetivoCurricular::create([
                'materia_id' => $materia->id,
                'subnivel' => 'basica_elemental',
                'codigo' => $codigo,
                'descripcion' => "Objetivo {$codigo}",
                'activo' => true,
            ]);
        }
    }
}
