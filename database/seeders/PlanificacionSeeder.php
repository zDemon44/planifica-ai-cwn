<?php

namespace Database\Seeders;

use App\Actions\ConstruirPeriodosPlanificacion;
use App\Models\Asignacion;
use App\Models\Planificacion;
use Illuminate\Database\Seeder;

class PlanificacionSeeder extends Seeder
{
    public function run(ConstruirPeriodosPlanificacion $construirPeriodos): void
    {
        $asignacion = Asignacion::with('seccion.periodo')
            ->whereHas('seccion.periodo', fn ($consulta) => $consulta->where('cerrado', false))
            ->orderBy('id')->first();

        if (! $asignacion) {
            return;
        }

        $inicio = $asignacion->seccion->periodo->fecha_inicio;
        $fin = $inicio->copy()->addDays(4)->min($asignacion->seccion->periodo->fecha_fin);

        $periodos = $construirPeriodos->handle($asignacion);
        if ($periodos->isEmpty()) {
            return;
        }

        $planificacion = Planificacion::firstOrCreate(
            ['asignacion_id' => $asignacion->id, 'numero_semana' => 1],
            ['trimestre' => 1, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'numero_periodos' => $periodos->sum(fn (array $periodo): int => $periodo['numero_fin'] - $periodo['numero_inicio'] + 1)],
        );

        if (! $planificacion->periodos()->exists()) {
            $planificacion->periodos()->createMany($periodos->map(
                fn (array $periodo): array => array_diff_key($periodo, ['dia' => true, 'incluye_recreo' => true])
            )->all());
        }
    }
}
