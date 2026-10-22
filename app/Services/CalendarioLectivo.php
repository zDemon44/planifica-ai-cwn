<?php

namespace App\Services;

use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CalendarioLectivo
{
    /**
     * @return array{semanas: Collection<int, array<string, mixed>>, total_dias_lectivos: int, total_dias_sin_clases: int}
     */
    public function construir(PeriodoAcademico $periodo): array
    {
        $periodo->loadMissing(['cierresTrimestrales', 'diasNoClase']);
        $cursor = $periodo->fecha_inicio->copy()->startOfWeek(CarbonInterface::MONDAY);
        $ultimaSemana = $periodo->fecha_fin->copy()->startOfWeek(CarbonInterface::MONDAY);
        $semanas = collect();

        while ($cursor->lte($ultimaSemana)) {
            $dias = collect(range(0, 4))->map(function (int $desplazamiento) use ($cursor, $periodo): ?array {
                $fecha = $cursor->copy()->addDays($desplazamiento);
                if ($fecha->lt($periodo->fecha_inicio) || $fecha->gt($periodo->fecha_fin)) {
                    return null;
                }

                $suspension = $periodo->diasNoClase->first(
                    fn ($dia): bool => $fecha->betweenIncluded($dia->fecha, $dia->fecha_fin ?? $dia->fecha),
                );

                return [
                    'fecha' => $fecha->format('Y-m-d'),
                    'dia' => ucfirst($fecha->locale('es')->translatedFormat('D')),
                    'numero' => $fecha->format('d'),
                    'mes' => $fecha->locale('es')->translatedFormat('M'),
                    'es_lectivo' => $suspension === null,
                    'motivo' => $suspension?->motivo,
                ];
            })->filter()->values();

            if ($dias->isNotEmpty()) {
                $fechaReferencia = Carbon::parse($dias->first()['fecha']);
                $semanas->push([
                    'numero' => $semanas->count() + 1,
                    'inicio' => $dias->first()['fecha'],
                    'fin' => $dias->last()['fecha'],
                    'trimestre' => $this->trimestre($periodo->cierresTrimestrales, $fechaReferencia),
                    'dias' => $dias,
                    'dias_lectivos' => $dias->where('es_lectivo', true)->count(),
                    'dias_sin_clases' => $dias->where('es_lectivo', false)->count(),
                ]);
            }

            $cursor->addWeek();
        }

        return [
            'semanas' => $semanas,
            'total_dias_lectivos' => $semanas->sum('dias_lectivos'),
            'total_dias_sin_clases' => $semanas->sum('dias_sin_clases'),
        ];
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
