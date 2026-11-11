<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculo extends Model
{
    public const CODIGO_VIGENTE = 'priorizado-2025';

    public const CODIGO_ANTERIOR = 'obligatorio-elemental';

    protected $fillable = ['codigo', 'nombre', 'vigente'];

    protected function casts(): array
    {
        return ['vigente' => 'boolean'];
    }

    public function destrezas(): HasMany
    {
        return $this->hasMany(DestrezaCurricular::class);
    }

    public function indicadores(): HasMany
    {
        return $this->hasMany(IndicadorEvaluacion::class);
    }
}
