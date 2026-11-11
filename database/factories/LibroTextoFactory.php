<?php

namespace Database\Factories;

use App\Models\LibroTexto;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibroTexto>
 */
class LibroTextoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'materia_id' => Materia::factory(),
            'codigo' => fake()->unique()->slug(3),
            'grado' => fake()->numberBetween(2, 4),
            'titulo' => fake()->sentence(4),
            'activo' => true,
        ];
    }
}
