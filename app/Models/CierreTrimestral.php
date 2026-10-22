<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreTrimestral extends Model
{
    protected $table = 'cierres_trimestrales';

    protected $fillable = ['periodo_academico_id', 'numero', 'fecha_fin'];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'fecha_fin' => 'date'];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }
}
