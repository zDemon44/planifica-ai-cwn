<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->unsignedTinyInteger('grado')->nullable()->after('nombre');
        });

        DB::table('cursos')->orderBy('id')->each(function (object $curso): void {
            $nombre = mb_strtolower(trim($curso->nombre));
            $grado = match (true) {
                str_contains($nombre, 'segundo') => 2,
                str_contains($nombre, 'tercero') => 3,
                str_contains($nombre, 'cuarto') => 4,
                default => null,
            };
            DB::table('cursos')->where('id', $curso->id)->update(['grado' => $grado]);
        });

        Schema::create('destrezas_curriculares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->unsignedTinyInteger('grado');
            $table->string('codigo', 60);
            $table->longText('descripcion');
            $table->string('competencia_curricular', 20)->nullable();
            $table->unsignedSmallInteger('grupo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['materia_id', 'grado', 'codigo'], 'destreza_materia_grado_codigo_unique');
        });

        Schema::create('indicadores_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->unsignedTinyInteger('grado');
            $table->string('codigo', 60);
            $table->longText('descripcion');
            $table->string('competencia_curricular', 20)->nullable();
            $table->unsignedSmallInteger('grupo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['materia_id', 'grado', 'codigo'], 'indicador_materia_grado_codigo_unique');
        });

        Schema::create('destreza_indicador', function (Blueprint $table) {
            $table->foreignId('destreza_curricular_id')->constrained('destrezas_curriculares')->cascadeOnDelete();
            $table->foreignId('indicador_evaluacion_id')->constrained('indicadores_evaluacion')->cascadeOnDelete();
            $table->primary(['destreza_curricular_id', 'indicador_evaluacion_id'], 'destreza_indicador_primary');
        });

        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->foreignId('destreza_curricular_id')->nullable()->after('hora_fin')->constrained('destrezas_curriculares')->restrictOnDelete();
            $table->foreignId('indicador_evaluacion_id')->nullable()->after('destreza_curricular_id')->constrained('indicadores_evaluacion')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('indicador_evaluacion_id');
            $table->dropConstrainedForeignId('destreza_curricular_id');
        });
        Schema::dropIfExists('destreza_indicador');
        Schema::dropIfExists('indicadores_evaluacion');
        Schema::dropIfExists('destrezas_curriculares');
        Schema::table('cursos', fn (Blueprint $table) => $table->dropColumn('grado'));
    }
};
