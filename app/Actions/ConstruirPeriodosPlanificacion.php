<?php

namespace App\Actions;

use App\Models\Asignacion;
use App\Models\Horario;
use App\Models\Recreo;
use Illuminate\Support\Collection;

class ConstruirPeriodosPlanificacion
{
    /** @return Collection<int, array{orden: int, numero_inicio: int, numero_fin: int, duracion_minutos: int, dia_semana: int, dia: string, hora_inicio: string, hora_fin: string, incluye_recreo?: bool}> */
    public function handle(Asignacion $asignacion): Collection
    {
        $asignacion->loadMissing('seccion.curso');
        $horario = Horario::with(['bloques' => fn ($consulta) => $consulta
            ->where('materia_id', $asignacion->materia_id)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')])
            ->where('docente_id', $asignacion->docente_id)
            ->where('seccion_id', $asignacion->seccion_id)
            ->first();
        $recreo = Recreo::where('periodo_academico_id', $asignacion->seccion->periodo_academico_id)
            ->where('subnivel', $asignacion->seccion->curso->subnivel)
            ->where('activo', true)
            ->first();

        $periodos = collect();
        $numeroPeriodo = 0;

        foreach ($horario?->bloques ?? collect() as $bloque) {
            $horaInicio = substr($bloque->hora_inicio, 0, 5);
            $horaFin = substr($bloque->hora_fin, 0, 5);
            $ultimo = $periodos->last();

            $continuaDespuesDelRecreo = $ultimo && $recreo
                && $ultimo['dia_semana'] === $bloque->dia_semana
                && in_array($bloque->dia_semana, $recreo->dias_semana, true)
                && $ultimo['hora_fin'] === substr($recreo->hora_inicio, 0, 5)
                && $horaInicio === substr($recreo->hora_fin, 0, 5);

            if ($ultimo && $ultimo['dia_semana'] === $bloque->dia_semana && ($ultimo['hora_fin'] === $horaInicio || $continuaDespuesDelRecreo)) {
                $numeroPeriodo++;
                $ultimo['numero_fin'] = $numeroPeriodo;
                $ultimo['duracion_minutos'] += 45;
                $ultimo['hora_fin'] = $horaFin;
                $ultimo['incluye_recreo'] = ($ultimo['incluye_recreo'] ?? false) || $continuaDespuesDelRecreo;
                $periodos->put($periodos->keys()->last(), $ultimo);

                continue;
            }

            $numeroPeriodo++;
            $periodos->push([
                'orden' => $periodos->count() + 1,
                'numero_inicio' => $numeroPeriodo,
                'numero_fin' => $numeroPeriodo,
                'duracion_minutos' => 45,
                'dia_semana' => $bloque->dia_semana,
                'dia' => Horario::DIAS[$bloque->dia_semana],
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'incluye_recreo' => false,
            ]);
        }

        return $periodos;
    }
}
