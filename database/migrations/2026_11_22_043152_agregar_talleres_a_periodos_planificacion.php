<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->boolean('es_taller')->default(false)->after('tema_libro_id');
        });

        Schema::create('periodo_planificacion_tema_libro', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periodo_planificacion_id')
                ->constrained('periodos_planificacion', indexName: 'taller_periodo_foreign')->cascadeOnDelete();
            $table->foreignId('tema_libro_id')
                ->constrained('temas_libro', indexName: 'taller_tema_foreign')->restrictOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->timestamps();
            $table->unique(['periodo_planificacion_id', 'tema_libro_id'], 'periodo_tema_taller_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_planificacion_tema_libro');

        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->dropColumn('es_taller');
        });
    }
};
