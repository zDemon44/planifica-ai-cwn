<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CursoFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => fake()->unique()->word(), 'subnivel' => 'basica_elemental', 'orden' => fake()->unique()->numberBetween(1, 100), 'es_final' => false];
    }
}
