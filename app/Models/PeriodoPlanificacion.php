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
        'fecha_clase', 'hora_inicio', 'hora_fin', 'destreza_curricular_id', 'indicador_evaluacion_id', 'destrezas_criterios_desempeno',
        'indicadores_evaluacion', 'recursos', 'tema', 'experiencia', 'reflexion',
        'conceptualizacion', 'aplicacion', 'orientaciones_evaluacion',
    ];

    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(Planificacion::class);
    }

    protected function casts(): array
    {
        return ['fecha_clase' => 'date'];
    }

    public function destrezaCurricular(): BelongsTo
    {
        return $this->belongsTo(DestrezaCurricular::class);
    }

    public function indicadorEvaluacion(): BelongsTo
    {
        return $this->belongsTo(IndicadorEvaluacion::class);
    }
}
