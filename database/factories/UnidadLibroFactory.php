<?php

namespace Database\Factories;

use App\Models\LibroTexto;
use App\Models\UnidadLibro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadLibro>
 */
class UnidadLibroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'libro_texto_id' => LibroTexto::factory(),
            'numero' => fake()->unique()->numberBetween(1, 20),
            'titulo' => fake()->sentence(5),
            'activo' => true,
        ];
    }
}
