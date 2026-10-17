<?php

namespace App\Models;

use Database\Factories\PeriodoPlanificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodoPlanificacion extends Model
{
    /** @use HasFactory<PeriodoPlanificacionFactory> */
    use HasFactory;

    protected $table = 'periodos_planificacion';

    protected $fillable = [
        'orden', 'numero_inicio', 'numero_fin', 'duracion_minutos', 'dia_semana',
        'hora_inicio', 'hora_fin', 'destreza_desempeno', 'indicadores_logro',
        'recursos', 'tema', 'anticipacion', 'construccion', 'consolidacion',
        'tecnicas_evaluacion',
    ];

    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(Planificacion::class);
    }
}
