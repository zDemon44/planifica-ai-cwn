<?php

namespace Database\Factories;

use App\Models\TemaLibro;
use App\Models\UnidadLibro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemaLibro>
 */
class TemaLibroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unidad_libro_id' => UnidadLibro::factory(),
            'orden' => fake()->unique()->numberBetween(1, 100),
            'etiqueta' => 'Tema '.fake()->numberBetween(1, 20),
            'titulo' => fake()->sentence(),
            'codigo_destreza_fuente' => 'M.2.1.'.fake()->numberBetween(1, 35),
            'codigo_indicador_fuente' => 'I.M.2.2.'.fake()->numberBetween(1, 5),
            'activo' => true,
        ];
    }
}
