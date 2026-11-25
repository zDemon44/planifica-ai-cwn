<?php

namespace App\Models;

use Database\Factories\PeriodoPlanificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PeriodoPlanificacion extends Model
{
    /** @use HasFactory<PeriodoPlanificacionFactory> */
    use HasFactory;

    protected $table = 'periodos_planificacion';

    protected $fillable = [
        'tema_libro_id', 'es_taller', 'orden', 'numero_inicio', 'numero_fin', 'duracion_minutos', 'dia_semana',
        'fecha_clase', 'hora_inicio', 'hora_fin', 'destreza_curricular_id', 'indicador_evaluacion_id', 'destrezas_criterios_desempeno',
        'indicadores_evaluacion', 'recursos', 'tema', 'experiencia', 'dua_experiencia',
        'reflexion', 'dua_reflexion', 'conceptualizacion', 'dua_conceptualizacion',
        'aplicacion', 'dua_aplicacion', 'orientaciones_evaluacion', 'tecnica_evaluacion',
        'instrumento_evaluacion',
    ];

    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(Planificacion::class);
    }

    public function temaLibro(): BelongsTo
    {
        return $this->belongsTo(TemaLibro::class);
    }

    /**
     * Temas del libro que se repasan en un bloque de taller, en el orden elegido por el docente.
     */
    public function temasTaller(): BelongsToMany
    {
        return $this->belongsToMany(TemaLibro::class, 'periodo_planificacion_tema_libro')
            ->withPivot('orden')
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    protected function casts(): array
    {
        return ['fecha_clase' => 'date', 'es_taller' => 'boolean'];
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
