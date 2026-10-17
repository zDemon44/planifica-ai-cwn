<?php

namespace Database\Seeders;

use App\Models\Asignacion;
use App\Models\Curso;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereIn('role', ['ADMIN', 'SUPERADMIN'])->firstOrFail();
        Auth::onceUsingId($admin->id);

        DB::transaction(function () {
            $periodo = PeriodoAcademico::where('cerrado', false)->orderByDesc('fecha_inicio')->first();
            if (! $periodo) {
                $ultimo = PeriodoAcademico::orderByDesc('fecha_fin')->first();
                $inicio = $ultimo ? $ultimo->fecha_fin->copy()->addDay() : now()->startOfYear();
                $periodo = PeriodoAcademico::create(['nombre' => 'DEMO '.$inicio->format('Y').'-'.$inicio->copy()->addYear()->format('Y'), 'fecha_inicio' => $inicio, 'fecha_fin' => $inicio->copy()->addYear()->subDay(), 'cerrado' => false]);
            }

            $cursos = Curso::orderBy('orden')->get();
            if ($cursos->isEmpty()) {
                foreach (['Octavo', 'Noveno', 'Décimo'] as $indice => $nombre) {
                    Curso::create(['nombre' => $nombre, 'orden' => $indice + 1, 'es_final' => false]);
                }
                $cursos = Curso::orderBy('orden')->get();
            }
            $secciones = collect();
            foreach ($cursos->take(3) as $curso) {
                foreach (['A', 'B'] as $paralelo) {
                    $secciones->push(Seccion::firstOrCreate(['curso_id' => $curso->id, 'periodo_academico_id' => $periodo->id, 'nombre' => $paralelo]));
                }
            }

            $docentes = collect();
            foreach ([['Ana', 'Torres'], ['Carlos', 'Mendoza'], ['Lucía', 'Vargas'], ['Pedro', 'Salazar']] as $indice => [$nombres, $apellidos]) {
                $docentes->push(Docente::firstOrCreate(['identificacion' => 'DEMO-DOC-'.($indice + 1)], ['nombres' => $nombres, 'apellidos' => $apellidos, 'correo' => 'docente'.($indice + 1).'@example.com', 'telefono' => '0000000000', 'estado' => 'activo']));
            }
            $materias = collect();
            foreach (['Matemáticas', 'Lengua y Literatura', 'Ciencias Naturales', 'Estudios Sociales'] as $nombre) {
                $materias->push(Materia::firstOrCreate(['nombre' => $nombre], ['descripcion' => 'Materia de demostración.', 'estado' => 'activo']));
            }
            foreach ($secciones as $seccion) {
                foreach ($materias as $indice => $materia) {
                    $docente = $docentes[[0, 3, 1, 2][$indice]];
                    if ($docente->estado === 'activo' && $materia->estado === 'activo') {
                        Asignacion::firstOrCreate(['materia_id' => $materia->id, 'seccion_id' => $seccion->id], ['docente_id' => $docente->id]);
                    }
                }
            }

        });
    }
}
