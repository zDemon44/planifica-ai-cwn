<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Materia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MateriasDocentePruebaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $ana = Docente::where('identificacion', 'DEMO-DOC-1')->firstOrFail();
            $pedro = Docente::where('identificacion', 'DEMO-DOC-4')->firstOrFail();
            $asignaciones = Asignacion::where('docente_id', $ana->id)
                ->whereHas('seccion.periodo', fn ($consulta) => $consulta->where('cerrado', false))
                ->with('materia')->lockForUpdate()->get();
            $fisica = Materia::firstOrCreate(['nombre' => 'Física'], ['descripcion' => 'Materia de demostración.', 'estado' => 'activo']);
            foreach ($asignaciones as $asignacion) {
                if ($asignacion->materia->nombre === 'Lengua y Literatura') {
                    $existente = Asignacion::where('docente_id', $pedro->id)->where('materia_id', $asignacion->materia_id)->where('seccion_id', $asignacion->seccion_id)->exists();
                    if (! $existente) {
                        $asignacion->update(['docente_id' => $pedro->id]);
                    }
                }
                if ($asignacion->materia->nombre === 'Matemáticas') {
                    Asignacion::firstOrCreate(['materia_id' => $fisica->id, 'seccion_id' => $asignacion->seccion_id], ['docente_id' => $ana->id]);
                }
            }
        });
    }
}
