<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('destrezas_curriculares', function (Blueprint $table) {
            $table->index('materia_id', 'destrezas_curriculares_materia_id_index');
        });
        Schema::table('indicadores_evaluacion', function (Blueprint $table) {
            $table->index('materia_id', 'indicadores_evaluacion_materia_id_index');
        });
        Schema::table('destrezas_curriculares', function (Blueprint $table) {
            $table->dropUnique('destreza_materia_grado_codigo_unique');
            $table->foreignId('curriculo_id')->default(1)->after('materia_id')
                ->constrained('curriculos')->restrictOnDelete();
            $table->unique(
                ['curriculo_id', 'materia_id', 'grado', 'codigo'],
                'destreza_curriculo_materia_grado_codigo_unique'
            );
        });
        Schema::table('indicadores_evaluacion', function (Blueprint $table) {
            $table->dropUnique('indicador_materia_grado_codigo_unique');
            $table->foreignId('curriculo_id')->default(1)->after('materia_id')
                ->constrained('curriculos')->restrictOnDelete();
            $table->unique(
                ['curriculo_id', 'materia_id', 'grado', 'codigo'],
                'indicador_curriculo_materia_grado_codigo_unique'
            );
        });

        $this->normalizarCodigosDeReferencia();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $destrezasAnteriores = DB::table('destrezas_curriculares')->where('curriculo_id', '<>', 1)->pluck('id');
        $indicadoresAnteriores = DB::table('indicadores_evaluacion')->where('curriculo_id', '<>', 1)->pluck('id');
        DB::table('destreza_indicador')->whereIn('destreza_curricular_id', $destrezasAnteriores)
            ->orWhereIn('indicador_evaluacion_id', $indicadoresAnteriores)->delete();
        DB::table('destrezas_curriculares')->whereIn('id', $destrezasAnteriores)->delete();
        DB::table('indicadores_evaluacion')->whereIn('id', $indicadoresAnteriores)->delete();

        DB::table('indicadores_evaluacion')->where('codigo', 'like', 'Ref.%')->orderBy('id')
            ->each(function (object $indicador): void {
                DB::table('indicadores_evaluacion')->where('id', $indicador->id)->update([
                    'codigo' => preg_replace('/^Ref\./u', '', $indicador->codigo),
                ]);
            });

        Schema::table('destrezas_curriculares', function (Blueprint $table) {
            $table->dropUnique('destreza_curriculo_materia_grado_codigo_unique');
            $table->dropConstrainedForeignId('curriculo_id');
            $table->unique(['materia_id', 'grado', 'codigo'], 'destreza_materia_grado_codigo_unique');
            $table->dropIndex('destrezas_curriculares_materia_id_index');
        });
        Schema::table('indicadores_evaluacion', function (Blueprint $table) {
            $table->dropUnique('indicador_curriculo_materia_grado_codigo_unique');
            $table->dropConstrainedForeignId('curriculo_id');
            $table->unique(['materia_id', 'grado', 'codigo'], 'indicador_materia_grado_codigo_unique');
            $table->dropIndex('indicadores_evaluacion_materia_id_index');
        });
    }

    private function normalizarCodigosDeReferencia(): void
    {
        $indicadores = DB::table('indicadores_evaluacion')->where('curriculo_id', 1)->get();
        $originales = $indicadores->where('grado', 4)->keyBy(
            fn (object $indicador): string => preg_replace('/^Ref\./u', '', $indicador->codigo)
        );

        foreach ($indicadores as $indicador) {
            $codigoBase = preg_replace('/^Ref\./u', '', $indicador->codigo);
            $descripcionOriginal = $originales->get($codigoBase)?->descripcion ?? '';
            $esReferencia = preg_match('/\(\s*Ref\.?\s*I\./iu', $descripcionOriginal) === 1;
            $codigo = $esReferencia ? 'Ref.'.$codigoBase : $codigoBase;

            if ($indicador->codigo !== $codigo) {
                DB::table('indicadores_evaluacion')->where('id', $indicador->id)->update(['codigo' => $codigo]);
            }
        }
    }
};
