<?php

namespace App\Models;

use Database\Factories\TemaLibroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemaLibro extends Model
{
    /** @use HasFactory<TemaLibroFactory> */
    use HasFactory;

    protected $table = 'temas_libro';

    protected $fillable = [
        'unidad_libro_id', 'destreza_curricular_id', 'indicador_evaluacion_id', 'orden', 'etiqueta',
        'titulo', 'codigo_destreza_fuente', 'codigo_indicador_fuente', 'pagina_inicio', 'pagina_fin', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'pagina_inicio' => 'integer',
            'pagina_fin' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function unidadLibro(): BelongsTo
    {
        return $this->belongsTo(UnidadLibro::class);
    }

    public function destrezaCurricular(): BelongsTo
    {
        return $this->belongsTo(DestrezaCurricular::class);
    }

    public function indicadorEvaluacion(): BelongsTo
    {
        return $this->belongsTo(IndicadorEvaluacion::class);
    }

    public function periodosPlanificacion(): HasMany
    {
        return $this->hasMany(PeriodoPlanificacion::class);
    }
}
