<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLES = ['SUPERADMIN' => 'Superadmin', 'ADMIN' => 'Administrador', 'DOCENTE' => 'Docente'];

    protected $attributes = ['activo' => true, 'version_acceso' => 1];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function esAdministrador(): bool
    {
        return $this->activo && in_array($this->role, ['SUPERADMIN', 'ADMIN'], true);
    }

    public function esDocente(): bool
    {
        return $this->activo && $this->role === 'DOCENTE' && $this->docente?->estado === 'activo';
    }

    public function puedeIngresar(): bool
    {
        return $this->activo && ($this->role !== 'DOCENTE' || $this->esDocente());
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'version_acceso' => 'integer',
        ];
    }
}
