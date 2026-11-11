<?php

namespace App\Models;

use Database\Factories\LibroTextoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibroTexto extends Model
{
    /** @use HasFactory<LibroTextoFactory> */
    use HasFactory;

    protected $table = 'libros_texto';

    protected $fillable = [
        'materia_id', 'codigo', 'grado', 'titulo', 'editorial', 'edicion', 'anio_publicacion',
        'archivo_origen', 'archivo_pdf', 'activo',
    ];

    protected function casts(): array
    {
        return ['grado' => 'integer', 'anio_publicacion' => 'integer', 'activo' => 'boolean'];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(UnidadLibro::class)->orderBy('numero');
    }
}
