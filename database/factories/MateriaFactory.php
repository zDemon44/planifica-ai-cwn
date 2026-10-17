<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MateriaFactory extends Factory
{
    public function definition(): array
    {
        return ['nombre' => fake()->unique()->words(3, true), 'descripcion' => fake()->sentence(), 'estado' => 'activo'];
    }
}
