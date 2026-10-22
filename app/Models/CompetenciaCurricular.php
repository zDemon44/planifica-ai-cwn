<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompetenciaCurricular extends Model
{
    protected $table = 'competencias_curriculares';

    protected $fillable = ['codigo'];

    public function destrezas(): BelongsToMany
    {
        return $this->belongsToMany(DestrezaCurricular::class, 'competencia_destreza');
    }

    public function indicadores(): BelongsToMany
    {
        return $this->belongsToMany(IndicadorEvaluacion::class, 'competencia_indicador');
    }
}
