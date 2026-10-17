<?php

namespace Database\Factories;

use App\Models\Asignacion;
use App\Models\PeriodoPlanificacion;
use App\Models\Planificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Planificacion>
 */
class PlanificacionFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Planificacion $planificacion): void {
            if (! $planificacion->periodos()->exists()) {
                foreach (range(1, $planificacion->numero_periodos) as $numero) {
                    PeriodoPlanificacion::factory()->create([
                        'planificacion_id' => $planificacion->id,
                        'orden' => $numero,
                        'numero_inicio' => $numero,
                        'numero_fin' => $numero,
                    ]);
                }
            }
        });
    }

    public function definition(): array
    {
        return [
            'asignacion_id' => Asignacion::factory(),
            'trimestre' => 1,
            'numero_semana' => 1,
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-05',
            'numero_periodos' => 3,
            'objetivo_disciplinar' => 'Objetivo de aprendizaje',
            'destreza_desempeno' => 'Destreza con criterio de desempeño',
            'indicadores_logro' => 'Indicadores de logro',
            'recursos' => 'Recursos didácticos',
            'estrategias_metodologicas' => 'Estrategias metodológicas',
            'tecnicas_evaluacion' => 'Técnicas e instrumentos de evaluación',
        ];
    }
}
