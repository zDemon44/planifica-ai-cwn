<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recreos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->restrictOnDelete();
            $table->string('subnivel', 30);
            $table->string('nombre', 60)->default('Recreo');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->json('dias_semana');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['periodo_academico_id', 'subnivel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recreos');
    }
};
