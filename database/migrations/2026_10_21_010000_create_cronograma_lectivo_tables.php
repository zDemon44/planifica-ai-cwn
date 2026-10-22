<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_trimestrales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->date('fecha_fin');
            $table->timestamps();
            $table->unique(['periodo_academico_id', 'numero']);
        });
        Schema::create('dias_no_clase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('motivo', 500);
            $table->timestamps();
            $table->unique(['periodo_academico_id', 'fecha']);
        });
        Schema::table('periodos_planificacion', function (Blueprint $table) {
            $table->date('fecha_clase')->nullable()->after('dia_semana');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_planificacion', fn (Blueprint $table) => $table->dropColumn('fecha_clase'));
        Schema::dropIfExists('dias_no_clase');
        Schema::dropIfExists('cierres_trimestrales');
    }
};
