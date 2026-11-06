<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ObjetivoCurricular extends Model
{
    protected $table = 'objetivos_curriculares';

    protected $fillable = ['materia_id', 'subnivel', 'codigo', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function planificaciones(): BelongsToMany
    {
        return $this->belongsToMany(Planificacion::class, 'objetivo_planificacion');
    }
}
