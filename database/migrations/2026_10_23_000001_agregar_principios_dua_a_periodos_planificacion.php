<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->longText('dua_experiencia')->nullable()->after('experiencia');
            $table->longText('dua_reflexion')->nullable()->after('reflexion');
            $table->longText('dua_conceptualizacion')->nullable()->after('conceptualizacion');
            $table->longText('dua_aplicacion')->nullable()->after('aplicacion');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_planificacion', function (Blueprint $table): void {
            $table->dropColumn([
                'dua_experiencia',
                'dua_reflexion',
                'dua_conceptualizacion',
                'dua_aplicacion',
            ]);
        });
    }
};
