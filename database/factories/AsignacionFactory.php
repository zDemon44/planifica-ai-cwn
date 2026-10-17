<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Materia;
use App\Models\Seccion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsignacionFactory extends Factory
{
    public function definition(): array
    {
        return ['docente_id' => Docente::factory(), 'materia_id' => Materia::factory(), 'seccion_id' => Seccion::factory()];
    }
}
