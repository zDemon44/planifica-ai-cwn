<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\PeriodoAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeccionFactory extends Factory
{
    public function definition(): array
    {
        return ['curso_id' => Curso::factory(), 'periodo_academico_id' => PeriodoAcademico::factory(), 'nombre' => 'A'];
    }
}
