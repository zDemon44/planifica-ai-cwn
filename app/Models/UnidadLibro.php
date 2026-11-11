<?php

namespace App\Models;

use Database\Factories\UnidadLibroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadLibro extends Model
{
    /** @use HasFactory<UnidadLibroFactory> */
    use HasFactory;

    protected $table = 'unidades_libro';

    protected $fillable = ['libro_texto_id', 'numero', 'titulo', 'activo'];

    protected function casts(): array
    {
        return ['numero' => 'integer', 'activo' => 'boolean'];
    }

    public function libroTexto(): BelongsTo
    {
        return $this->belongsTo(LibroTexto::class);
    }

    public function objetivos(): BelongsToMany
    {
        return $this->belongsToMany(ObjetivoCurricular::class, 'objetivo_unidad_libro');
    }

    public function temas(): HasMany
    {
        return $this->hasMany(TemaLibro::class)->orderBy('orden');
    }

    public function planificaciones(): HasMany
    {
        return $this->hasMany(Planificacion::class);
    }
}
