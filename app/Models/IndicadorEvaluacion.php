<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicadorEvaluacion extends Model
{
    protected $table = 'indicadores_evaluacion';

    protected $fillable = ['materia_id', 'grado', 'codigo', 'descripcion', 'grupo', 'activo'];

    protected function casts(): array
    {
        return ['grado' => 'integer', 'grupo' => 'integer', 'activo' => 'boolean'];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function destrezas(): BelongsToMany
    {
        return $this->belongsToMany(DestrezaCurricular::class, 'destreza_indicador');
    }

    public function competencias(): BelongsToMany
    {
        return $this->belongsToMany(CompetenciaCurricular::class, 'competencia_indicador');
    }

    public function periodosPlanificacion(): HasMany
    {
        return $this->hasMany(PeriodoPlanificacion::class);
    }
}
