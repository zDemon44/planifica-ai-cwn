<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_planificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planificacion_id')->constrained('planificaciones')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden');
            $table->unsignedSmallInteger('numero_inicio');
            $table->unsignedSmallInteger('numero_fin');
            $table->unsignedSmallInteger('duracion_minutos');
            $table->unsignedTinyInteger('dia_semana')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->text('destreza_desempeno')->nullable();
            $table->text('indicadores_logro')->nullable();
            $table->text('recursos')->nullable();
            $table->text('tema')->nullable();
            $table->text('anticipacion')->nullable();
            $table->text('construccion')->nullable();
            $table->text('consolidacion')->nullable();
            $table->text('tecnicas_evaluacion')->nullable();
            $table->timestamps();
            $table->unique(['planificacion_id', 'orden']);
        });

        DB::table('planificaciones')->orderBy('id')->each(function (object $planificacion): void {
            DB::table('periodos_planificacion')->insert([
                'planificacion_id' => $planificacion->id,
                'orden' => 1,
                'numero_inicio' => 1,
                'numero_fin' => $planificacion->numero_periodos,
                'duracion_minutos' => $planificacion->numero_periodos * 45,
                'destreza_desempeno' => $planificacion->destreza_desempeno,
                'indicadores_logro' => $planificacion->indicadores_logro,
                'recursos' => $planificacion->recursos,
                'construccion' => $planificacion->estrategias_metodologicas,
                'tecnicas_evaluacion' => $planificacion->tecnicas_evaluacion,
                'created_at' => $planificacion->created_at,
                'updated_at' => $planificacion->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_planificacion');
    }
};
