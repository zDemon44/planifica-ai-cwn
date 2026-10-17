<?php

namespace Database\Factories;

use App\Models\PeriodoAcademico;
use App\Models\Recreo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recreo>
 */
class RecreoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodo_academico_id' => PeriodoAcademico::factory(),
            'subnivel' => 'basica_elemental',
            'nombre' => 'Recreo',
            'hora_inicio' => '09:20',
            'hora_fin' => '09:50',
            'dias_semana' => [1, 2, 3, 4, 5],
            'activo' => true,
        ];
    }
}
