<?php

namespace Database\Seeders;

use App\Models\BloqueHorario;
use App\Models\Horario;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horario = Horario::factory()->create();
        $materias = Materia::factory()->count(4)->create();

        foreach ($materias as $indice => $materia) {
            BloqueHorario::factory()->create([
                'horario_id' => $horario->id,
                'materia_id' => $materia->id,
                'dia_semana' => $indice + 1,
            ]);
        }
    }
}
