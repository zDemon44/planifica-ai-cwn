<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'materia_id');
    }

    public function bloquesHorario(): HasMany
    {
        return $this->hasMany(BloqueHorario::class);
    }

    public function destrezasCurriculares(): HasMany
    {
        return $this->hasMany(DestrezaCurricular::class);
    }

    public function indicadoresEvaluacion(): HasMany
    {
        return $this->hasMany(IndicadorEvaluacion::class);
    }
}
