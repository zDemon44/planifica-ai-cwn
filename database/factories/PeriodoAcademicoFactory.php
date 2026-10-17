<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoAcademicoFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => fake()->unique()->word(), 'fecha_inicio' => '2026-04-01', 'fecha_fin' => '2027-03-01', 'cerrado' => false];
    }
}
