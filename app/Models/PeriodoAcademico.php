<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoAcademico extends Model
{
    protected $table = 'periodos_academicos';

    use HasFactory;

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'cerrado'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'cerrado' => 'boolean'];
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class, 'periodo_academico_id');
    }

    public function recreos(): HasMany
    {
        return $this->hasMany(Recreo::class, 'periodo_academico_id');
    }

    public function cierresTrimestrales(): HasMany
    {
        return $this->hasMany(CierreTrimestral::class)->orderBy('numero');
    }

    public function diasNoClase(): HasMany
    {
        return $this->hasMany(DiaNoClase::class)->orderBy('fecha');
    }
}
