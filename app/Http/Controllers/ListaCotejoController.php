<?php

namespace App\Http\Controllers;

use App\Models\PeriodoPlanificacion;
use App\Models\Planificacion;
use App\Services\LectorInstrumentoEvaluacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ListaCotejoController extends Controller
{
    /**
     * Rangos de la escala numérica según cuántos niveles tenga el instrumento.
     */
    private const RANGOS = [
        1 => ['10-1'],
        2 => ['10-7', '6-1'],
        3 => ['10-9', '8-7', '6-5'],
        4 => ['10-9', '8-7', '6-5', '4-1'],
        5 => ['10-9', '8-7', '6-5', '4-3', '2-1'],
    ];

    /**
     * Descarga el instrumento de un bloque como lista de cotejo en PDF.
     */
    public function show(Planificacion $planificacion, PeriodoPlanificacion $periodo, LectorInstrumentoEvaluacion $lector): Response
    {
        Gate::authorize('view', $planificacion);
        $instrumento = $lector->leer($periodo->instrumento_evaluacion);
        abort_unless($instrumento, 404, 'El instrumento de este bloque no tiene criterios numerados para armar la lista de cotejo.');

        $planificacion->load(['asignacion.materia', 'asignacion.seccion.curso']);
        $niveles = $instrumento['escala'] ?: ['Logrado', 'No logrado'];
        $logo = public_path('images/logo-cwn-pdf.png');

        $pdf = Pdf::setOption(['isFontSubsettingEnabled' => true])->loadView('Planificaciones.lista-cotejo', [
            'planificacion' => $planificacion,
            'periodo' => $periodo,
            'instrumento' => $instrumento,
            'niveles' => $niveles,
            'valoracion' => $this->valoracion($niveles),
            'logo' => extension_loaded('gd') && is_file($logo) ? $logo : null,
        ])->setPaper('a4', 'portrait');

        $nombre = Str::slug("lista de cotejo {$planificacion->asignacion->materia->nombre} semana {$planificacion->numero_semana} bloque {$periodo->orden}");

        return $pdf->download("{$nombre}.pdf");
    }

    /**
     * @param  list<string>  $niveles
     * @return list<array{nivel: string, descripcion: string, escala: string}>
     */
    private function valoracion(array $niveles): array
    {
        $cantidad = count($niveles);
        $intermedias = [
            'Cumple en gran parte con el criterio evaluado, pero presenta algunas deficiencias.',
            'Cumple parcialmente con el criterio evaluado.',
            'Cumple mínimamente con el criterio evaluado.',
        ];

        return array_map(fn (string $nivel, int $posicion): array => [
            'nivel' => $nivel,
            'descripcion' => match (true) {
                $posicion === 0 => 'Cumple completamente con el criterio evaluado.',
                $posicion === $cantidad - 1 => 'No cumple con el criterio evaluado.',
                default => $intermedias[$posicion - 1] ?? $intermedias[2],
            },
            'escala' => self::RANGOS[$cantidad][$posicion],
        ], $niveles, array_keys($niveles));
    }
}
