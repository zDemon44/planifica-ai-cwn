<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->restrictOnDelete();
            $table->unsignedTinyInteger('trimestre');
            $table->unsignedTinyInteger('numero_semana');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('numero_periodos');
            $table->string('area', 120)->nullable();
            $table->text('objetivo_disciplinar')->nullable();
            $table->text('estandar_aprendizaje')->nullable();
            $table->text('destreza_desempeno')->nullable();
            $table->text('indicadores_logro')->nullable();
            $table->text('recursos')->nullable();
            $table->longText('estrategias_metodologicas')->nullable();
            $table->text('tecnicas_evaluacion')->nullable();
            $table->text('adaptacion_especificacion')->nullable();
            $table->text('adaptacion_destreza')->nullable();
            $table->text('adaptacion_indicadores')->nullable();
            $table->text('adaptacion_recursos')->nullable();
            $table->text('adaptacion_estrategias')->nullable();
            $table->text('adaptacion_evaluacion')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->unsignedInteger('version')->default(1);
            $table->text('observaciones_revision')->nullable();
            $table->timestamp('enviado_en')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamps();
            $table->unique(['asignacion_id', 'numero_semana'], 'planificacion_asignacion_semana_unica');
            $table->index(['estado', 'enviado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planificaciones');
    }
};
