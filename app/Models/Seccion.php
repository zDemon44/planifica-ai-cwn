<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seccion extends Model
{
    protected $table = 'secciones';

    use HasFactory;

    protected $fillable = ['curso_id', 'periodo_academico_id', 'nombre'];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function horario(): HasOne
    {
        return $this->hasOne(Horario::class);
    }

    public function getEtiquetaAttribute(): string
    {
        return $this->curso->nombre.' · '.$this->nombre.' · '.$this->periodo->nombre;
    }
}
