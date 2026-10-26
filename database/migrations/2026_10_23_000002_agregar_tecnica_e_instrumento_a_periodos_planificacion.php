<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->longText('tecnica_evaluacion')->nullable()->after('orientaciones_evaluacion');
            $table->longText('instrumento_evaluacion')->nullable()->after('tecnica_evaluacion');
        });

        DB::table('periodos_planificacion')
            ->whereNotNull('orientaciones_evaluacion')
            ->update(['tecnica_evaluacion' => DB::raw('orientaciones_evaluacion')]);
    }

    public function down(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->dropColumn(['tecnica_evaluacion', 'instrumento_evaluacion']);
        });
    }
};
