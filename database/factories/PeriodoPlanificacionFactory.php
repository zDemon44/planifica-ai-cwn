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
            'destrezas_criterios_desempeno' => 'Destrezas con criterios de desempeño',
            'indicadores_evaluacion' => 'Indicadores de evaluación',
            'recursos' => 'Recursos didácticos',
            'tema' => 'Tema de la clase',
            'experiencia' => 'Activación de conocimientos previos',
            'reflexion' => 'Preguntas para analizar la experiencia',
            'conceptualizacion' => 'Construcción de los nuevos conceptos',
            'aplicacion' => 'Aplicación de lo aprendido',
            'orientaciones_evaluacion' => 'Observación directa y lista de cotejo',
        ];
    }
}
