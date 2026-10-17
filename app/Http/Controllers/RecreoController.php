<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRecreoRequest;
use App\Models\Curso;
use App\Models\PeriodoAcademico;
use App\Models\Recreo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecreoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('gestionar-docencia');

        return view('Recreos.index', [
            'recreos' => Recreo::with('periodo')->orderByDesc('periodo_academico_id')->orderBy('subnivel')->paginate(12),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('gestionar-docencia');

        return $this->formulario(new Recreo);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GuardarRecreoRequest $request): RedirectResponse
    {
        $recreo = DB::transaction(function () use ($request): Recreo {
            $periodo = PeriodoAcademico::lockForUpdate()->findOrFail($request->integer('periodo_academico_id'));
            if ($periodo->cerrado) {
                throw ValidationException::withMessages(['periodo_academico_id' => 'El período seleccionado está cerrado.']);
            }

            return Recreo::create($this->datos($request));
        }, 3);

        return redirect()->route('recreos.index')->with('success', "{$recreo->nombre} configurado correctamente.");
    }

    /**
     * Display the specified resource.
     */
    public function edit(Recreo $recreo): View
    {
        Gate::authorize('gestionar-docencia');

        return $this->formulario($recreo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GuardarRecreoRequest $request, Recreo $recreo): RedirectResponse
    {
        DB::transaction(function () use ($request, $recreo): void {
            $registro = Recreo::lockForUpdate()->findOrFail($recreo->id);
            $periodos = PeriodoAcademico::whereIn('id', [$registro->periodo_academico_id, $request->integer('periodo_academico_id')])->lockForUpdate()->get();
            if ($periodos->contains('cerrado', true)) {
                throw ValidationException::withMessages(['periodo_academico_id' => 'No se puede modificar el recreo de un período cerrado.']);
            }
            $registro->update($this->datos($request));
        }, 3);

        return redirect()->route('recreos.index')->with('success', 'Recreo actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    private function formulario(Recreo $recreo): View
    {
        return view('Recreos.form', [
            'recreo' => $recreo,
            'periodos' => PeriodoAcademico::where('cerrado', false)->orderByDesc('fecha_inicio')->get(),
            'subniveles' => Curso::SUBNIVELES,
        ]);
    }

    /** @return array<string, mixed> */
    private function datos(GuardarRecreoRequest $request): array
    {
        return [
            ...$request->safe()->only(['periodo_academico_id', 'subnivel', 'nombre', 'hora_inicio', 'hora_fin', 'dias_semana']),
            'activo' => $request->boolean('activo'),
        ];
    }
}
