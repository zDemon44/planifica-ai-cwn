<?php

namespace App\Models;

use Database\Factories\PlanificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planificacion extends Model
{
    /** @use HasFactory<PlanificacionFactory> */
    use HasFactory;

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'en_revision' => 'En revisión',
        'revisada' => 'Revisada',
        'devuelta' => 'Devuelta',
        'aprobada' => 'Aprobada',
    ];

    protected $table = 'planificaciones';

    protected $fillable = [
        'asignacion_id', 'trimestre', 'numero_semana', 'fecha_inicio', 'fecha_fin',
        'numero_periodos', 'area', 'objetivo_disciplinar', 'estandar_aprendizaje',
        'destreza_desempeno', 'indicadores_logro', 'recursos',
        'estrategias_metodologicas', 'tecnicas_evaluacion',
        'adaptacion_especificacion', 'adaptacion_destreza',
        'adaptacion_indicadores', 'adaptacion_recursos',
        'adaptacion_estrategias', 'adaptacion_evaluacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'enviado_en' => 'datetime',
            'revisado_en' => 'datetime',
            'aprobado_en' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function periodos(): HasMany
    {
        return $this->hasMany(PeriodoPlanificacion::class)->orderBy('orden');
    }
}
