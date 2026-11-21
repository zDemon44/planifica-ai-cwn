<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerarPeriodoIaRequest;
use App\Models\Asignacion;
use App\Models\DestrezaCurricular;
use App\Models\IndicadorEvaluacion;
use App\Services\GeneradorPeriodoPlanificacion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Throwable;
use UnexpectedValueException;

class AsistentePlanificacionController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    public function store(
        GenerarPeriodoIaRequest $request,
        GeneradorPeriodoPlanificacion $generador,
    ): JsonResponse {
        $asignacion = Asignacion::with(['materia', 'seccion.curso', 'seccion.periodo'])
            ->where('docente_id', $request->user()->docente_id)
            ->whereHas('seccion.periodo', fn ($consulta) => $consulta->where('cerrado', false))
            ->findOrFail($request->integer('asignacion_id'));
        $grado = $asignacion->seccion->curso->grado;

        $destreza = DestrezaCurricular::whereKey($request->integer('destreza_curricular_id'))
            ->where('materia_id', $asignacion->materia_id)
            ->where('grado', $grado)
            ->where('activo', true)
            ->first();
        $indicador = IndicadorEvaluacion::whereKey($request->integer('indicador_evaluacion_id'))
            ->where('materia_id', $asignacion->materia_id)
            ->where('grado', $grado)
            ->where('activo', true)
            ->first();

        if (! $destreza || ! $indicador || ! $destreza->indicadores()->whereKey($indicador->id)->exists()) {
            return response()->json([
                'message' => 'Selecciona una destreza y uno de sus indicadores relacionados.',
                'errors' => ['indicador_evaluacion_id' => ['El indicador no corresponde a la destreza seleccionada.']],
            ], 422);
        }

        try {
            $propuesta = $generador->generar(
                $asignacion,
                $destreza,
                $indicador,
                $request->integer('duracion_minutos'),
                $request->string('titulo_unidad')->trim()->toString() ?: null,
                $request->string('tema')->trim()->toString() ?: null,
                $request->string('indicaciones')->trim()->toString() ?: null,
            );
        } catch (UnexpectedValueException $exception) {
            report($exception);

            return response()->json([
                'message' => 'El asistente generó una respuesta incompleta. Inténtalo nuevamente.',
            ], 502);
        } catch (ConnectionException $exception) {
            report($exception);

            if (str_contains(mb_strtolower($exception->getMessage()), 'timed out')) {
                return response()->json([
                    'message' => 'La generación con Gemini superó el tiempo máximo. Inténtalo nuevamente con indicaciones más breves.',
                ], 504);
            }

            return response()->json([
                'message' => 'No pudimos comunicarnos con Gemini. Verifica la conexión e inténtalo nuevamente.',
            ], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No pudimos comunicarnos con Gemini. Verifica la conexión e inténtalo nuevamente.',
            ], 503);
        }

        return response()->json([
            'message' => 'Propuesta generada. Revísala antes de guardar el borrador.',
            'propuesta' => $propuesta,
        ]);
    }
}
