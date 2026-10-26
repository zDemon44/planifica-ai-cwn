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
            'dua_experiencia' => 'Ofrecer distintas formas de participación y motivación',
            'reflexion' => 'Preguntas para analizar la experiencia',
            'dua_reflexion' => 'Facilitar distintas formas de expresar las ideas',
            'conceptualizacion' => 'Construcción de los nuevos conceptos',
            'dua_conceptualizacion' => 'Presentar la información en distintos formatos',
            'aplicacion' => 'Aplicación de lo aprendido',
            'dua_aplicacion' => 'Permitir distintas formas de demostrar el aprendizaje',
            'tecnica_evaluacion' => 'Observación directa',
            'instrumento_evaluacion' => 'Lista de cotejo',
        ];
    }
}
