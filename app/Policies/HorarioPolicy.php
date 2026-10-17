<?php

namespace App\Policies;

use App\Models\Horario;
use App\Models\User;

class HorarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador() || $user->esDocente();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Horario $horario): bool
    {
        return $user->esAdministrador() || ($user->esDocente() && $horario->docente_id === $user->docente_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Horario $horario): bool
    {
        return $user->esAdministrador() && ! $horario->periodo->cerrado;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Horario $horario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Horario $horario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Horario $horario): bool
    {
        return false;
    }
}
