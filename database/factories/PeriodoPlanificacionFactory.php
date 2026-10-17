<?php

namespace Database\Factories;

use App\Models\PeriodoPlanificacion;
use App\Models\Planificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodoPlanificacion>
 */
class PeriodoPlanificacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'planificacion_id' => Planificacion::factory(),
            'orden' => 1,
            'numero_inicio' => 1,
            'numero_fin' => 1,
            'duracion_minutos' => 45,
            'dia_semana' => 1,
            'hora_inicio' => '07:00',
            'hora_fin' => '07:45',
            'destreza_desempeno' => 'Destreza con criterio de desempeño',
            'indicadores_logro' => 'Indicadores de logro',
            'recursos' => 'Recursos didácticos',
            'tema' => 'Tema de la clase',
            'anticipacion' => 'Activación de conocimientos previos',
            'construccion' => 'Desarrollo del aprendizaje',
            'consolidacion' => 'Aplicación de lo aprendido',
            'tecnicas_evaluacion' => 'Observación y rúbrica',
        ];
    }
}
