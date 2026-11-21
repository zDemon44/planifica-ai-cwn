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
            $table->string('jornada', 30)->nullable()->change();
        });

        DB::table('planificaciones')->update([
            'jornada' => 'Matutina - Vespertina',
        ]);
    }

    public function down(): void
    {
        DB::table('planificaciones')->update([
            'jornada' => 'matutina',
        ]);

        Schema::table('planificaciones', function (Blueprint $table) {
            $table->string('jornada', 20)->nullable()->change();
        });
    }
};
