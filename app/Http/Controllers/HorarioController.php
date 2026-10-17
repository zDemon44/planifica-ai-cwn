<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarHorarioRequest;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Recreo;
use App\Models\Seccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HorarioController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        Gate::authorize('viewAny', Horario::class);
        $usuario = $request->user();

        if ($usuario->esDocente()) {
            $horario = Horario::where('docente_id', $usuario->docente_id)
                ->whereHas('periodo', fn ($consulta) => $consulta->where('cerrado', false))
                ->orderByDesc('periodo_academico_id')->first()
                ?? Horario::where('docente_id', $usuario->docente_id)->orderByDesc('periodo_academico_id')->first();

            if ($horario) {
                return redirect()->route('horarios.show', $horario);
            }
        }

        $horarios = Horario::with(['docente', 'seccion.curso', 'periodo', 'bloques.materia'])
            ->orderByDesc('periodo_academico_id')->orderBy('seccion_id')->paginate(12);

        return view('Horarios.index', compact('horarios'));
    }

    public function create(): View
    {
        Gate::authorize('create', Horario::class);

        return $this->formulario(new Horario);
    }

    public function store(GuardarHorarioRequest $request): RedirectResponse
    {
        Gate::authorize('create', Horario::class);
        $horario = DB::transaction(function () use ($request): Horario {
            [$seccion, $periodo] = $this->validarContexto($request, null);
            $horario = Horario::create(['docente_id' => $request->integer('docente_id'), 'seccion_id' => $seccion->id, 'periodo_academico_id' => $periodo->id]);
            $this->guardarBloques($horario, $request->validated('bloques'));

            return $horario;
        }, 3);

        return redirect()->route('horarios.show', $horario)->with('success', 'Horario creado y asignaciones docentes actualizadas.');
    }

    public function show(Horario $horario): View
    {
        Gate::authorize('view', $horario);
        $horario->load(['docente', 'seccion.curso', 'periodo', 'bloques.materia']);

        $recreo = Recreo::where('periodo_academico_id', $horario->periodo_academico_id)
            ->where('subnivel', $horario->seccion->curso->subnivel)->where('activo', true)->first();

        return view('Horarios.show', compact('horario', 'recreo'));
    }

    public function edit(Horario $horario): View
    {
        Gate::authorize('update', $horario);

        return $this->formulario($horario->load('bloques'));
    }

    public function update(GuardarHorarioRequest $request, Horario $horario): RedirectResponse
    {
        DB::transaction(function () use ($request, $horario): void {
            $registro = Horario::lockForUpdate()->findOrFail($horario->id);
            Gate::authorize('update', $registro);
            if ($registro->version !== $request->integer('version')) {
                throw ValidationException::withMessages(['version' => 'El horario cambió mientras lo editabas. Recarga la página.']);
            }
            [$seccion, $periodo] = $this->validarContexto($request, $registro);
            $registro->update(['docente_id' => $request->integer('docente_id'), 'seccion_id' => $seccion->id, 'periodo_academico_id' => $periodo->id, 'version' => $registro->version + 1]);
            $registro->bloques()->delete();
            $this->guardarBloques($registro, $request->validated('bloques'));
        }, 3);

        return redirect()->route('horarios.show', $horario)->with('success', 'Horario actualizado.');
    }

    private function formulario(Horario $horario): View
    {
        $docentes = Docente::where('estado', 'activo')->orderBy('apellidos')->orderBy('nombres')->get();
        $materias = Materia::where('estado', 'activo')->orderBy('nombre')->get();
        $secciones = Seccion::with(['curso', 'periodo'])->whereHas('periodo', fn ($consulta) => $consulta->where('cerrado', false))
            ->where(fn ($consulta) => $consulta->whereDoesntHave('horario')->when($horario->exists, fn ($opciones) => $opciones->orWhereKey($horario->seccion_id)))
            ->get()->sortBy('etiqueta')->values();

        $recreos = Recreo::where('activo', true)->get()->keyBy(fn (Recreo $recreo): string => $recreo->periodo_academico_id.'-'.$recreo->subnivel);

        return view('Horarios.form', compact('horario', 'docentes', 'materias', 'secciones', 'recreos'));
    }

    /** @return array{Seccion, PeriodoAcademico} */
    private function validarContexto(GuardarHorarioRequest $request, ?Horario $horario): array
    {
        $seccion = Seccion::with('periodo')->lockForUpdate()->findOrFail($request->integer('seccion_id'));
        $periodo = PeriodoAcademico::lockForUpdate()->findOrFail($seccion->periodo_academico_id);
        if ($periodo->cerrado) {
            throw ValidationException::withMessages(['seccion_id' => 'No se puede modificar el horario de un período cerrado.']);
        }
        if (Horario::where('seccion_id', $seccion->id)->when($horario, fn ($consulta) => $consulta->whereKeyNot($horario->id))->exists()) {
            throw ValidationException::withMessages(['seccion_id' => 'Este curso ya tiene un horario.']);
        }
        if (Horario::where('docente_id', $request->integer('docente_id'))->where('periodo_academico_id', $periodo->id)->when($horario, fn ($consulta) => $consulta->whereKeyNot($horario->id))->exists()) {
            throw ValidationException::withMessages(['docente_id' => 'El docente ya es responsable de otro curso en este período.']);
        }

        return [$seccion, $periodo];
    }

    /** @param array<int, array{materia_id: int|string, dia_semana: int|string, hora_inicio: string, hora_fin: string}> $bloques */
    private function guardarBloques(Horario $horario, array $bloques): void
    {
        $asignacionesDeOtrosCursos = Asignacion::where('docente_id', $horario->docente_id)
            ->where('seccion_id', '!=', $horario->seccion_id)
            ->whereHas('seccion', fn ($consulta) => $consulta->where('periodo_academico_id', $horario->periodo_academico_id))
            ->withCount('planificaciones')->lockForUpdate()->get();
        if ($asignacionesDeOtrosCursos->contains(fn (Asignacion $asignacion) => $asignacion->planificaciones_count > 0)) {
            throw ValidationException::withMessages(['docente_id' => 'El docente conserva planificaciones en otro curso de este período. Revisa esas asignaciones antes de cambiarlo.']);
        }
        Asignacion::whereKey($asignacionesDeOtrosCursos->pluck('id'))->delete();

        $materias = collect($bloques)->pluck('materia_id')->map(fn ($id) => (int) $id)->unique();
        foreach ($materias as $materiaId) {
            $conflictos = Asignacion::where('seccion_id', $horario->seccion_id)->where('materia_id', $materiaId)->where('docente_id', '!=', $horario->docente_id)
                ->withCount('planificaciones')->lockForUpdate()->get();
            if ($conflictos->contains(fn (Asignacion $asignacion) => $asignacion->planificaciones_count > 0)) {
                throw ValidationException::withMessages(['bloques' => 'Una materia conserva planificaciones de otro docente. Revisa sus asignaciones antes de cambiar el responsable.']);
            }
            Asignacion::whereKey($conflictos->pluck('id'))->delete();
            Asignacion::firstOrCreate(['docente_id' => $horario->docente_id, 'materia_id' => $materiaId, 'seccion_id' => $horario->seccion_id]);
        }
        $horario->bloques()->createMany($bloques);
    }
}
