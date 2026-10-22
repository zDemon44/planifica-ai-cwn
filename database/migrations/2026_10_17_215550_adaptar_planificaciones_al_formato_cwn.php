<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->string('titulo_unidad')->nullable()->after('trimestre');
            $table->string('jornada', 20)->nullable()->after('titulo_unidad');
            $table->longText('objetivos_aprendizaje')->nullable()->after('numero_periodos');
            $table->longText('adaptacion_indicadores_evaluacion')->nullable()->after('adaptacion_destreza');
            $table->longText('adaptacion_orientaciones_metodologicas')->nullable()->after('adaptacion_recursos');
            $table->longText('adaptacion_actividades_evaluacion_formativa')->nullable()->after('adaptacion_orientaciones_metodologicas');
        });

        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->longText('destrezas_criterios_desempeno')->nullable()->after('hora_fin');
            $table->longText('indicadores_evaluacion')->nullable()->after('destrezas_criterios_desempeno');
            $table->longText('experiencia')->nullable()->after('tema');
            $table->longText('reflexion')->nullable()->after('experiencia');
            $table->longText('conceptualizacion')->nullable()->after('reflexion');
            $table->longText('aplicacion')->nullable()->after('conceptualizacion');
            $table->longText('orientaciones_evaluacion')->nullable()->after('aplicacion');
        });

        DB::table('planificaciones')->orderBy('id')->each(function (object $planificacion): void {
            DB::table('planificaciones')->where('id', $planificacion->id)->update([
                'objetivos_aprendizaje' => $planificacion->objetivo_disciplinar,
                'adaptacion_indicadores_evaluacion' => $planificacion->adaptacion_indicadores,
                'adaptacion_orientaciones_metodologicas' => $planificacion->adaptacion_estrategias,
                'adaptacion_actividades_evaluacion_formativa' => $planificacion->adaptacion_evaluacion,
            ]);
        });

        DB::table('periodos_planificacion')->orderBy('id')->each(function (object $periodo): void {
            DB::table('periodos_planificacion')->where('id', $periodo->id)->update([
                'destrezas_criterios_desempeno' => $periodo->destreza_desempeno,
                'indicadores_evaluacion' => $periodo->indicadores_logro,
                'experiencia' => $periodo->anticipacion,
                'conceptualizacion' => $periodo->construccion,
                'aplicacion' => $periodo->consolidacion,
                'orientaciones_evaluacion' => $periodo->tecnicas_evaluacion,
            ]);
        });

        Schema::table('planificaciones', function (Blueprint $table) {
            $table->dropColumn([
                'area', 'objetivo_disciplinar', 'estandar_aprendizaje',
                'destreza_desempeno', 'indicadores_logro', 'recursos',
                'estrategias_metodologicas', 'tecnicas_evaluacion',
                'adaptacion_especificacion', 'adaptacion_indicadores',
                'adaptacion_estrategias', 'adaptacion_evaluacion',
            ]);
        });

        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->dropColumn([
                'destreza_desempeno', 'indicadores_logro', 'anticipacion',
                'construccion', 'consolidacion', 'tecnicas_evaluacion',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->string('area', 120)->nullable();
            $table->text('objetivo_disciplinar')->nullable();
            $table->text('estandar_aprendizaje')->nullable();
            $table->text('destreza_desempeno')->nullable();
            $table->text('indicadores_logro')->nullable();
            $table->text('recursos')->nullable();
            $table->longText('estrategias_metodologicas')->nullable();
            $table->text('tecnicas_evaluacion')->nullable();
            $table->text('adaptacion_especificacion')->nullable();
            $table->text('adaptacion_indicadores')->nullable();
            $table->text('adaptacion_estrategias')->nullable();
            $table->text('adaptacion_evaluacion')->nullable();
        });

        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->text('destreza_desempeno')->nullable();
            $table->text('indicadores_logro')->nullable();
            $table->text('anticipacion')->nullable();
            $table->text('construccion')->nullable();
            $table->text('consolidacion')->nullable();
            $table->text('tecnicas_evaluacion')->nullable();
        });

        DB::table('planificaciones')->orderBy('id')->each(function (object $planificacion): void {
            DB::table('planificaciones')->where('id', $planificacion->id)->update([
                'objetivo_disciplinar' => $planificacion->objetivos_aprendizaje,
                'adaptacion_indicadores' => $planificacion->adaptacion_indicadores_evaluacion,
                'adaptacion_estrategias' => $planificacion->adaptacion_orientaciones_metodologicas,
                'adaptacion_evaluacion' => $planificacion->adaptacion_actividades_evaluacion_formativa,
            ]);
        });

        DB::table('periodos_planificacion')->orderBy('id')->each(function (object $periodo): void {
            DB::table('periodos_planificacion')->where('id', $periodo->id)->update([
                'destreza_desempeno' => $periodo->destrezas_criterios_desempeno,
                'indicadores_logro' => $periodo->indicadores_evaluacion,
                'anticipacion' => $periodo->experiencia,
                'construccion' => $periodo->conceptualizacion,
                'consolidacion' => $periodo->aplicacion,
                'tecnicas_evaluacion' => $periodo->orientaciones_evaluacion,
            ]);
        });

        Schema::table('planificaciones', function (Blueprint $table) {
            $table->dropColumn([
                'titulo_unidad', 'jornada', 'objetivos_aprendizaje',
                'adaptacion_indicadores_evaluacion',
                'adaptacion_orientaciones_metodologicas',
                'adaptacion_actividades_evaluacion_formativa',
            ]);
        });

        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->dropColumn([
                'destrezas_criterios_desempeno', 'indicadores_evaluacion',
                'experiencia', 'reflexion', 'conceptualizacion', 'aplicacion',
                'orientaciones_evaluacion',
            ]);
        });
    }
};
