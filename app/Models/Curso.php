<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    public const SUBNIVELES = [
        'inicial' => 'Educación Inicial',
        'preparatoria' => 'Preparatoria',
        'basica_elemental' => 'Básica Elemental',
        'basica_media' => 'Básica Media',
        'basica_superior' => 'Básica Superior',
        'bachillerato' => 'Bachillerato',
    ];

    protected $table = 'cursos';

    use HasFactory;

    protected $fillable = ['nombre', 'subnivel', 'orden', 'es_final'];

    protected function casts(): array
    {
        return ['es_final' => 'boolean', 'orden' => 'integer'];
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class);
    }
}
