<?php

namespace App\Services;

use App\Actions\ConstruirPeriodosPlanificacion;
use App\Models\Asignacion;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CalendarioPlanificaciones
{
    public function __construct(private ConstruirPeriodosPlanificacion $construirPeriodos) {}

    /** @return Collection<int, array{numero:int,inicio:string,fin:string,trimestre:int,bloques:Collection}> */
    public function semanas(Asignacion $asignacion): Collection
    {
        $asignacion->loadMissing('seccion.periodo.cierresTrimestrales');
        $periodo = $asignacion->seccion->periodo;
        $cursor = $periodo->fecha_inicio->copy()->startOfWeek(CarbonInterface::MONDAY);
        $ultimo = $periodo->fecha_fin->copy()->startOfWeek(CarbonInterface::MONDAY);
        $semanas = collect();
        while ($cursor->lte($ultimo)) {
            $bloques = $this->construirPeriodos->handle($asignacion, $cursor);
            if ($bloques->isNotEmpty()) {
                $inicio = $cursor->copy()->max($periodo->fecha_inicio);
                $fin = $cursor->copy()->endOfWeek(CarbonInterface::SUNDAY)->min($periodo->fecha_fin);
                $semanas->push([
                    'numero' => $semanas->count() + 1,
                    'inicio' => $inicio->format('Y-m-d'),
                    'fin' => $fin->format('Y-m-d'),
                    'trimestre' => $this->trimestre($periodo->cierresTrimestrales, $inicio),
                    'bloques' => $bloques,
                ]);
            }
            $cursor->addWeek();
        }

        return $semanas;
    }

    private function trimestre(Collection $cierres, Carbon $fecha): int
    {
        foreach ($cierres->sortBy('numero') as $cierre) {
            if ($fecha->lte($cierre->fecha_fin)) {
                return $cierre->numero;
            }
        }

        return $cierres->isEmpty() ? 1 : 3;
    }
}
