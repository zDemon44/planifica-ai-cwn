<?php

namespace App\Policies;

use App\Models\Planificacion;
use App\Models\User;

class PlanificacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador() || $user->esDocente();
    }

    public function view(User $user, Planificacion $planificacion): bool
    {
        return $user->esAdministrador() || ($user->esDocente() && $planificacion->asignacion->docente_id === $user->docente_id);
    }

    public function create(User $user): bool
    {
        return $user->esDocente();
    }

    public function update(User $user, Planificacion $planificacion): bool
    {
        return $this->view($user, $planificacion)
            && $user->esDocente()
            && in_array($planificacion->estado, ['borrador', 'devuelta'], true)
            && ! $planificacion->asignacion->seccion->periodo->cerrado;
    }

    public function submit(User $user, Planificacion $planificacion): bool
    {
        return $this->update($user, $planificacion);
    }

    public function review(User $user, Planificacion $planificacion): bool
    {
        return $user->esAdministrador()
            && $planificacion->estado === 'en_revision'
            && ! $planificacion->asignacion->seccion->periodo->cerrado;
    }

    public function approve(User $user, Planificacion $planificacion): bool
    {
        return $user->esAdministrador()
            && $planificacion->estado === 'revisada'
            && ! $planificacion->asignacion->seccion->periodo->cerrado;
    }

    public function devolver(User $user, Planificacion $planificacion): bool
    {
        return $user->esAdministrador()
            && in_array($planificacion->estado, ['en_revision', 'revisada'], true)
            && ! $planificacion->asignacion->seccion->periodo->cerrado;
    }
}
