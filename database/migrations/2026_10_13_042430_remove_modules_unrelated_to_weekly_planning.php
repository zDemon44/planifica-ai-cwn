<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $subinspectoras = DB::table('users')->where('role', 'SUBINSPECTORA')->pluck('id');

        if ($subinspectoras->isNotEmpty() && Schema::hasTable('planificaciones')) {
            DB::table('planificaciones')->whereIn('revisado_por', $subinspectoras)->update(['revisado_por' => null, 'revisado_en' => null]);
            DB::table('planificaciones')->whereIn('aprobado_por', $subinspectoras)->update(['aprobado_por' => null, 'aprobado_en' => null]);
        }

        Schema::dropIfExists('calificaciones');
        Schema::dropIfExists('evaluaciones');
        Schema::dropIfExists('detalles_asistencia');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('cambios_paralelo');
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('tutorias');
        Schema::dropIfExists('estudiantes');

        DB::table('users')->where('role', 'SUBINSPECTORA')->delete();
    }

    public function down(): void {}
};
