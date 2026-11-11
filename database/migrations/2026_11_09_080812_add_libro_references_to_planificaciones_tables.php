<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table): void {
            $table->foreignId('unidad_libro_id')->nullable()->after('asignacion_id')
                ->constrained('unidades_libro')->restrictOnDelete();
        });

        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->foreignId('tema_libro_id')->nullable()->after('planificacion_id')
                ->constrained('temas_libro')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tema_libro_id');
        });

        Schema::table('planificaciones', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('unidad_libro_id');
        });
    }
};
