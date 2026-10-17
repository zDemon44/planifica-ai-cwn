<?php

namespace App\Livewire\Academico;

use App\Models\Curso;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Catalogo extends Component
{
    #[Locked]
    public string $tipoCatalogo = 'cursos';

    #[Locked]
    public ?int $idEdicion = null;

    #[Locked]
    public ?int $idEliminacion = null;

    public bool $mostrarFormulario = false;

    public array $formulario = [];

    public function tab(string $tipoCatalogo): void
    {
        $this->autorizarAcceso();
        abort_unless(in_array($tipoCatalogo, ['cursos', 'periodos', 'secciones'], true), 404);
        $this->tipoCatalogo = $tipoCatalogo;
        $this->cancel();
    }

    public function create(): void
    {
        $this->autorizarAcceso();
        $this->cancel();
        $this->formulario = ['nombre' => '', 'subnivel' => '', 'orden' => '', 'es_final' => false, 'fecha_inicio' => '', 'fecha_fin' => '', 'curso_id' => '', 'periodo_academico_id' => ''];
        $this->mostrarFormulario = true;
    }

    public function edit(int $id): void
    {
        $this->autorizarAcceso();
        $registro = $this->model()->findOrFail($id);
        $this->create();
        $this->idEdicion = $id;
        foreach (array_keys($this->formulario) as $campo) {
            if (in_array($campo, ['fecha_inicio', 'fecha_fin'], true)) {
                $this->formulario[$campo] = $registro->$campo?->format('Y-m-d') ?? '';
            } elseif ($registro->getAttribute($campo) !== null) {
                $this->formulario[$campo] = $registro->$campo;
            }
        }
    }

    public function save(): void
    {
        $this->autorizarAcceso();
        $registro = $this->idEdicion ? $this->model()->findOrFail($this->idEdicion) : null;
        $this->formulario['nombre'] = trim($this->formulario['nombre'] ?? '');
        $reglas = match ($this->tipoCatalogo) {
            'cursos' => [
                'formulario.nombre' => ['required', 'string', 'max:100', Rule::unique('cursos', 'nombre')->ignore($registro)],
                'formulario.subnivel' => ['required', 'string', Rule::in(array_keys(Curso::SUBNIVELES))],
                'formulario.orden' => ['required', 'integer', 'min:1', 'max:100', Rule::unique('cursos', 'orden')->ignore($registro)],
                'formulario.es_final' => ['required', 'boolean'],
            ],
            'periodos' => [
                'formulario.nombre' => ['required', 'string', 'max:100', Rule::unique('periodos_academicos', 'nombre')->ignore($registro)],
                'formulario.fecha_inicio' => ['required', 'date_format:Y-m-d'],
                'formulario.fecha_fin' => ['required', 'date_format:Y-m-d', 'after:formulario.fecha_inicio'],
            ],
            'secciones' => [
                'formulario.nombre' => ['required', 'string', 'max:30', Rule::unique('secciones', 'nombre')->where('curso_id', $this->formulario['curso_id'] ?? null)->where('periodo_academico_id', $this->formulario['periodo_academico_id'] ?? null)->ignore($registro)],
                'formulario.curso_id' => ['required', 'integer', 'exists:cursos,id'],
                'formulario.periodo_academico_id' => ['required', 'integer', 'exists:periodos_academicos,id'],
            ],
        };
        $datos = $this->validate($reglas, ['required' => 'Este campo es obligatorio.', 'unique' => 'Este valor ya está registrado.', 'after' => 'El fin debe ser posterior al inicio.', 'exists' => 'Selecciona una opción válida.', 'max' => 'Supera el máximo permitido.', 'integer' => 'Ingresa un número entero.', 'date_format' => 'Ingresa una fecha válida.'])['formulario'];
        DB::transaction(function () use ($registro, $datos) {
            if ($registro) {
                $registro = $this->model()->lockForUpdate()->findOrFail($registro->id);
                $this->comprobar(! $this->tieneReferencias($registro), 'Este registro ya tiene historial académico y no puede modificarse.');
            }
            if ($this->tipoCatalogo === 'cursos') {
                $otros = Curso::where('id', '!=', $registro?->id ?? 0);
                $this->comprobar(! $datos['es_final'] || ! (clone $otros)->where('orden', '>=', $datos['orden'])->exists(), 'El nivel final debe ser el último del orden.');
                $this->comprobar(! (clone $otros)->where('es_final', true)->where('orden', '<=', $datos['orden'])->exists(), 'Ya existe un nivel final anterior. Revisa el orden de los cursos.');
                $this->comprobar(! $datos['es_final'] || ! (clone $otros)->where('es_final', true)->exists(), 'Solo debe existir un nivel final.');
            }
            if ($this->tipoCatalogo === 'periodos') {
                $this->comprobar(! PeriodoAcademico::where('id', '!=', $registro?->id ?? 0)->where('fecha_inicio', '<=', $datos['fecha_fin'])->where('fecha_fin', '>=', $datos['fecha_inicio'])->exists(), 'Las fechas se superponen con otro período.');
                $this->comprobar(! $registro?->cerrado, 'No se puede editar un período cerrado.');
            }
            if ($this->tipoCatalogo === 'secciones') {
                $periodo = PeriodoAcademico::lockForUpdate()->findOrFail($datos['periodo_academico_id']);
                $this->comprobar(! $periodo->cerrado, 'El período seleccionado está cerrado.');
                $this->comprobar(! $registro || ! $registro->periodo->cerrado, 'La sección pertenece a un período cerrado.');
            }
            if ($registro) {
                $registro->update($datos);
            } else {
                $this->model()->create($datos);
            }
        });
        $this->cancel();
        session()->flash('catalogo_correcto', 'Registro guardado correctamente.');
    }

    public function confirmarEliminacion(int $id): void
    {
        $this->autorizarAcceso();
        $registro = $this->model()->findOrFail($id);
        $this->comprobar(! $this->tieneReferencias($registro), 'No puedes eliminar un registro con secciones o historial asociado.');
        $this->idEliminacion = $id;
    }

    public function delete(): void
    {
        $this->autorizarAcceso();
        DB::transaction(function () {
            $registro = $this->model()->lockForUpdate()->findOrFail($this->idEliminacion);
            $this->comprobar(! $this->tieneReferencias($registro), 'No puedes eliminar un registro con secciones o historial asociado.');
            $registro->delete();
        });
        $this->cancel();
        session()->flash('catalogo_correcto', 'Registro eliminado.');
    }

    public function cerrarPeriodo(int $id): void
    {
        $this->autorizarAcceso();
        DB::transaction(function () use ($id) {
            $periodo = PeriodoAcademico::lockForUpdate()->findOrFail($id);
            $periodo->update(['cerrado' => true]);
        });
        session()->flash('catalogo_correcto', 'Período cerrado. Su historial queda conservado.');
    }

    public function cancel(): void
    {
        $this->reset('idEdicion', 'idEliminacion', 'mostrarFormulario', 'formulario');
        $this->resetValidation();
    }

    private function model(): Model
    {
        return match ($this->tipoCatalogo) {
            'cursos' => new Curso, 'periodos' => new PeriodoAcademico, 'secciones' => new Seccion
        };
    }

    private function tieneReferencias(Model $registro): bool
    {
        return match ($this->tipoCatalogo) {
            'cursos' => $registro->secciones()->exists(),
            'periodos' => $registro->secciones()->exists(),
            'secciones' => $registro->asignaciones()->exists() || $registro->horario()->exists(),
        };
    }

    private function autorizarAcceso(): void
    {
        Gate::authorize('gestionar-docencia');
    }

    private function comprobar(bool $condicion, string $mensaje): void
    {
        if (! $condicion) {
            throw ValidationException::withMessages(['catalogo' => $mensaje]);
        }
    }

    public function render(): View
    {
        $this->autorizarAcceso();

        return view('livewire.academico.catalogo', [
            'cursos' => Curso::orderBy('orden')->get(),
            'periodos' => PeriodoAcademico::orderByDesc('fecha_inicio')->get(),
            'secciones' => Seccion::with(['curso', 'periodo'])->orderByDesc('periodo_academico_id')->orderBy('curso_id')->orderBy('nombre')->get(),
            'registroEliminar' => $this->idEliminacion ? $this->model()->find($this->idEliminacion) : null,
        ]);
    }
}
