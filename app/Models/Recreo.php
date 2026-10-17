<?php

namespace App\Models;

use Database\Factories\RecreoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recreo extends Model
{
    /** @use HasFactory<RecreoFactory> */
    use HasFactory;

    protected $table = 'recreos';

    protected $fillable = ['periodo_academico_id', 'subnivel', 'nombre', 'hora_inicio', 'hora_fin', 'dias_semana', 'activo'];

    protected function casts(): array
    {
        return ['dias_semana' => 'array', 'activo' => 'boolean'];
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }
}
