<?php

namespace Database\Factories;

use App\Models\BloqueHorario;
use App\Models\Horario;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloqueHorario>
 */
class BloqueHorarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'horario_id' => Horario::factory(),
            'materia_id' => Materia::factory(),
            'dia_semana' => fake()->numberBetween(1, 5),
            'hora_inicio' => '07:00',
            'hora_fin' => '07:40',
        ];
    }
}
