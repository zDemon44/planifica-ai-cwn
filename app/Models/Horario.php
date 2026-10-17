<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    use HasFactory;

    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

    protected $table = 'horarios';

    protected $fillable = ['docente_id', 'seccion_id', 'periodo_academico_id', 'version'];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }

    public function bloques(): HasMany
    {
        return $this->hasMany(BloqueHorario::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }
}
