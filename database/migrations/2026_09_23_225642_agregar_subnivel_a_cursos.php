<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('subnivel', 30)->nullable()->after('nombre')->index();
        });

        foreach ([
            'inicial' => ['Inicial', 'Educación Inicial'],
            'preparatoria' => ['Primero', 'Primer Grado'],
            'basica_elemental' => ['Segundo', 'Segundo Grado', 'Tercero', 'Tercer Grado', 'Cuarto', 'Cuarto Grado'],
            'basica_media' => ['Quinto', 'Quinto Grado', 'Sexto', 'Sexto Grado', 'Séptimo', 'Séptimo Grado'],
            'basica_superior' => ['Octavo', 'Octavo Grado', 'Noveno', 'Noveno Grado', 'Décimo', 'Décimo Grado'],
            'bachillerato' => ['Primero de Bachillerato', 'Segundo de Bachillerato', 'Tercero de Bachillerato'],
        ] as $subnivel => $nombres) {
            DB::table('cursos')->whereIn('nombre', $nombres)->update(['subnivel' => $subnivel]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropIndex(['subnivel']);
            $table->dropColumn('subnivel');
        });
    }
};
