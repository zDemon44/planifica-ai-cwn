<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueHorario extends Model
{
    use HasFactory;

    protected $table = 'bloque_horarios';

    protected $fillable = ['horario_id', 'materia_id', 'dia_semana', 'hora_inicio', 'hora_fin'];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }
}
