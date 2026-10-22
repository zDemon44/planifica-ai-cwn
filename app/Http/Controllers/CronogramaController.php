<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarCronogramaRequest;
use App\Models\DiaNoClase;
use App\Models\PeriodoAcademico;
use App\Services\CalendarioLectivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CronogramaController extends Controller
{
    public function __construct(private CalendarioLectivo $calendarioLectivo) {}

    public function index(Request $request): View
    {
        Gate::authorize('ver-cronograma');
        $datos = $request->validate(['periodo_id' => ['nullable', 'integer', 'exists:periodos_academicos,id']]);
        $periodos = PeriodoAcademico::orderByDesc('fecha_inicio')->get();
        $periodo = PeriodoAcademico::with(['cierresTrimestrales', 'diasNoClase'])->find($datos['periodo_id'] ?? $periodos->first()?->id);
        $calendario = $periodo
            ? $this->calendarioLectivo->construir($periodo)
            : ['semanas' => collect(), 'total_dias_lectivos' => 0, 'total_dias_sin_clases' => 0];

        return view('Cronogramas.index', compact('periodos', 'periodo', 'calendario'));
    }

    public function update(GuardarCronogramaRequest $request, PeriodoAcademico $periodo): RedirectResponse
    {
        $datos = $request->validated();
        $superpuesto = PeriodoAcademico::whereKeyNot($periodo->id)->where('fecha_inicio', '<=', $datos['fecha_fin'])->where('fecha_fin', '>=', $datos['fecha_inicio'])->exists();
        if ($superpuesto) {
            throw ValidationException::withMessages(['fecha_inicio' => 'Estas fechas se superponen con otro período académico.']);
        }
        DB::transaction(function () use ($periodo, $datos): void {
            $periodo->update(['fecha_inicio' => $datos['fecha_inicio'], 'fecha_fin' => $datos['fecha_fin']]);
            foreach ([1, 2, 3] as $numero) {
                $periodo->cierresTrimestrales()->updateOrCreate(['numero' => $numero], ['fecha_fin' => $datos["trimestre_{$numero}_fin"]]);
            }
            $periodo->diasNoClase()->where(function ($query) use ($datos): void {
                $query->whereDate('fecha', '<', $datos['fecha_inicio'])
                    ->orWhereDate('fecha', '>', $datos['fecha_fin'])
                    ->orWhereDate('fecha_fin', '>', $datos['fecha_fin']);
            })->delete();
        });

        return back()->with('success', 'Cronograma lectivo actualizado.');
    }

    public function storeDiaNoClase(Request $request, PeriodoAcademico $periodo): RedirectResponse
    {
        Gate::authorize('gestionar-docencia');
        $datos = $request->validate([
            'tipo_suspension' => ['required', 'in:dia,rango'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'required_if:tipo_suspension,rango', 'date_format:Y-m-d', 'after_or_equal:fecha'],
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'tipo_suspension.required' => 'Selecciona si registrarás un día o un rango de fechas.',
            'fecha_fin.required_if' => 'Indica la fecha final del rango.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ]);
        $esRango = $datos['tipo_suspension'] === 'rango';
        $fechaFin = $esRango ? $datos['fecha_fin'] : $datos['fecha'];
        if ($datos['fecha'] < $periodo->fecha_inicio->format('Y-m-d') || $fechaFin > $periodo->fecha_fin->format('Y-m-d')) {
            throw ValidationException::withMessages(['fecha' => 'El día o período debe estar dentro del año lectivo.']);
        }

        $registroMismoInicio = $periodo->diasNoClase()->whereDate('fecha', $datos['fecha'])->first();
        $seSuperpone = $periodo->diasNoClase()
            ->when($registroMismoInicio, fn ($consulta) => $consulta->whereKeyNot($registroMismoInicio->id))
            ->whereDate('fecha', '<=', $fechaFin)
            ->where(function ($consulta) use ($datos): void {
                $consulta->whereDate('fecha_fin', '>=', $datos['fecha'])
                    ->orWhere(function ($consulta) use ($datos): void {
                        $consulta->whereNull('fecha_fin')->whereDate('fecha', '>=', $datos['fecha']);
                    });
            })->exists();
        if ($seSuperpone) {
            throw ValidationException::withMessages(['fecha' => 'Este período se cruza con otro día sin clases ya registrado.']);
        }

        $periodo->diasNoClase()->updateOrCreate(
            ['fecha' => $datos['fecha']],
            ['fecha_fin' => $esRango ? $datos['fecha_fin'] : null, 'motivo' => trim($datos['motivo'])],
        );

        return back()->with('success', $esRango ? 'Período sin clases registrado.' : 'Día sin clases registrado.');
    }

    public function destroyDiaNoClase(PeriodoAcademico $periodo, DiaNoClase $diaNoClase): RedirectResponse
    {
        Gate::authorize('gestionar-docencia');
        abort_unless($diaNoClase->periodo_academico_id === $periodo->id, 404);
        $diaNoClase->delete();

        return back()->with('success', 'El día vuelve a estar disponible para clases.');
    }
}
