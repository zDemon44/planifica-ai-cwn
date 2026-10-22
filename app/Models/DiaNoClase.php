<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaNoClase extends Model
{
    protected $table = 'dias_no_clase';

    protected $fillable = ['periodo_academico_id', 'fecha', 'fecha_fin', 'motivo'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'fecha_fin' => 'date'];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }
}
