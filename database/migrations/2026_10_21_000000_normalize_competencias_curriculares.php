<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencias_curriculares', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->timestamps();
        });
        Schema::create('competencia_destreza', function (Blueprint $table) {
            $table->foreignId('competencia_curricular_id')->constrained('competencias_curriculares')->cascadeOnDelete();
            $table->foreignId('destreza_curricular_id')->constrained('destrezas_curriculares')->cascadeOnDelete();
            $table->primary(['competencia_curricular_id', 'destreza_curricular_id'], 'competencia_destreza_primary');
        });
        Schema::create('competencia_indicador', function (Blueprint $table) {
            $table->foreignId('competencia_curricular_id')->constrained('competencias_curriculares')->cascadeOnDelete();
            $table->foreignId('indicador_evaluacion_id')->constrained('indicadores_evaluacion')->cascadeOnDelete();
            $table->primary(['competencia_curricular_id', 'indicador_evaluacion_id'], 'competencia_indicador_primary');
        });

        $this->migrarCompetencias('destrezas_curriculares', 'destreza_curricular_id', 'competencia_destreza');
        $this->migrarCompetencias('indicadores_evaluacion', 'indicador_evaluacion_id', 'competencia_indicador');

        Schema::table('destrezas_curriculares', fn (Blueprint $table) => $table->dropColumn('competencia_curricular'));
        Schema::table('indicadores_evaluacion', fn (Blueprint $table) => $table->dropColumn('competencia_curricular'));
    }

    public function down(): void
    {
        Schema::table('destrezas_curriculares', fn (Blueprint $table) => $table->string('competencia_curricular', 255)->nullable());
        Schema::table('indicadores_evaluacion', fn (Blueprint $table) => $table->string('competencia_curricular', 255)->nullable());

        DB::table('destrezas_curriculares')->orderBy('id')->each(function (object $registro): void {
            $codigos = DB::table('competencia_destreza')->join('competencias_curriculares', 'competencias_curriculares.id', '=', 'competencia_destreza.competencia_curricular_id')
                ->where('destreza_curricular_id', $registro->id)->orderBy('codigo')->pluck('codigo')->implode(',');
            DB::table('destrezas_curriculares')->where('id', $registro->id)->update(['competencia_curricular' => $codigos ?: null]);
        });
        DB::table('indicadores_evaluacion')->orderBy('id')->each(function (object $registro): void {
            $codigos = DB::table('competencia_indicador')->join('competencias_curriculares', 'competencias_curriculares.id', '=', 'competencia_indicador.competencia_curricular_id')
                ->where('indicador_evaluacion_id', $registro->id)->orderBy('codigo')->pluck('codigo')->implode(',');
            DB::table('indicadores_evaluacion')->where('id', $registro->id)->update(['competencia_curricular' => $codigos ?: null]);
        });

        Schema::dropIfExists('competencia_indicador');
        Schema::dropIfExists('competencia_destreza');
        Schema::dropIfExists('competencias_curriculares');
    }

    private function migrarCompetencias(string $tablaOrigen, string $columnaForanea, string $tablaPivote): void
    {
        DB::table($tablaOrigen)->whereNotNull('competencia_curricular')->orderBy('id')->each(function (object $registro) use ($columnaForanea, $tablaPivote): void {
            $codigos = array_values(array_unique(array_filter(array_map('trim', explode(',', $registro->competencia_curricular)))));
            foreach ($codigos as $codigo) {
                $id = DB::table('competencias_curriculares')->where('codigo', $codigo)->value('id');
                if (! $id) {
                    $id = DB::table('competencias_curriculares')->insertGetId(['codigo' => $codigo, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table($tablaPivote)->insertOrIgnore(['competencia_curricular_id' => $id, $columnaForanea => $registro->id]);
            }
        });
    }
};
