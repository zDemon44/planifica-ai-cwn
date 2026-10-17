<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['busqueda' => ['nullable', 'string', 'max:100']]);
        $busqueda = trim($datos['busqueda'] ?? '');
        $usuarios = User::with('docente')->when($busqueda !== '', fn ($consulta) => $consulta->where(fn ($consulta) => $consulta->where('name', 'like', '%'.$busqueda.'%')->orWhere('email', 'like', '%'.$busqueda.'%')))->orderBy('name')->orderBy('id')->paginate(10)->withQueryString();

        return view('Usuarios.index', ['usuarios' => $usuarios, 'busqueda' => $busqueda, 'roles' => User::ROLES]);
    }

    public function create(): View
    {
        return $this->formulario(new User);
    }

    public function edit(User $usuario): View
    {
        return $this->formulario($usuario);
    }

    private function formulario(User $usuario): View
    {
        return view('Usuarios.form', ['usuario' => $usuario, 'roles' => User::ROLES, 'docentes' => Docente::where(fn ($consulta) => $consulta->where('estado', 'activo')->orWhere('id', $usuario->docente_id))->whereDoesntHave('usuario', fn ($consulta) => $consulta->where('id', '!=', $usuario->id ?? 0))->orderBy('apellidos')->get()]);
    }

    public function show(User $usuario): View
    {
        $usuario->load('docente');

        return view('Usuarios.show', ['usuario' => $usuario, 'roles' => User::ROLES]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->guardar($request);

        return redirect()->route('usuarios.show', $usuario)->with('success', 'Cuenta creada correctamente.');
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario = $this->guardar($request, $usuario);

        return redirect()->route('usuarios.show', $usuario)->with('success', 'Cuenta actualizada correctamente.');
    }

    private function guardar(GuardarUsuarioRequest $request, ?User $usuario = null): User
    {
        return DB::transaction(function () use ($request, $usuario) {
            $administradores = User::where('role', 'SUPERADMIN')->orderBy('id')->lockForUpdate()->get();
            $actor = $administradores->firstWhere('id', $request->user()->id);
            abort_unless($actor?->activo, 403);
            $datos = $request->validated();
            $registro = $usuario ? User::lockForUpdate()->findOrFail($usuario->id) : new User;
            if ($registro->id === $actor->id && (! $datos['activo'] || $datos['role'] !== 'SUPERADMIN')) {
                throw ValidationException::withMessages(['role' => 'No puedes desactivar tu propia cuenta ni quitarte el rol Superadmin.']);
            }
            if ($registro->role === 'SUPERADMIN' && $registro->activo && (! $datos['activo'] || $datos['role'] !== 'SUPERADMIN') && $administradores->where('activo', true)->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Debe quedar al menos un Superadmin activo.']);
            }
            if (! empty($datos['docente_id'])) {
                $docente = Docente::lockForUpdate()->findOrFail($datos['docente_id']);
                if (($datos['activo'] && $docente->estado !== 'activo') || User::where('docente_id', $docente->id)->where('id', '!=', $registro->id ?? 0)->exists()) {
                    throw ValidationException::withMessages(['docente_id' => 'El docente no está disponible para esta cuenta.']);
                }
            }
            $registro->name = $datos['name'];
            $registro->email = $datos['email'];
            $registro->role = $datos['role'];
            $registro->activo = (bool) $datos['activo'];
            $registro->docente_id = $datos['role'] === 'DOCENTE' ? $datos['docente_id'] : null;
            if (! empty($datos['password'])) {
                $registro->password = $datos['password'];
            }
            if ($registro->exists && $registro->isDirty(['email', 'password', 'role', 'activo', 'docente_id'])) {
                $registro->version_acceso++;
                $registro->remember_token = Str::random(60);
            }
            $registro->save();
            if ($registro->id === $actor->id) {
                $request->session()->put('version_acceso', $registro->version_acceso);
                $request->session()->regenerate();
            }

            return $registro;
        }, 3);
    }
}
