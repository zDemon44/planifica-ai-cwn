<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Horario;
use App\Models\Seccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horario>
 */
class HorarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'docente_id' => Docente::factory(),
            'seccion_id' => Seccion::factory(),
            'periodo_academico_id' => function (array $atributos): int {
                return Seccion::findOrFail($atributos['seccion_id'])->periodo_academico_id;
            },
            'version' => 1,
        ];
    }
}
