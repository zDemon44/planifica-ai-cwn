<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('libros_texto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->string('codigo', 80)->unique();
            $table->unsignedTinyInteger('grado');
            $table->string('titulo');
            $table->string('editorial')->nullable();
            $table->string('edicion')->nullable();
            $table->unsignedSmallInteger('anio_publicacion')->nullable();
            $table->string('archivo_origen', 500)->nullable();
            $table->string('archivo_pdf', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('unidades_libro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_texto_id')->constrained('libros_texto')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('titulo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['libro_texto_id', 'numero'], 'unidad_libro_numero_unique');
        });

        Schema::create('objetivo_unidad_libro', function (Blueprint $table) {
            $table->foreignId('objetivo_curricular_id')->constrained('objetivos_curriculares')->restrictOnDelete();
            $table->foreignId('unidad_libro_id')->constrained('unidades_libro')->cascadeOnDelete();
            $table->primary(['objetivo_curricular_id', 'unidad_libro_id'], 'objetivo_unidad_libro_primary');
        });

        Schema::create('temas_libro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_libro_id')->constrained('unidades_libro')->cascadeOnDelete();
            $table->foreignId('destreza_curricular_id')->nullable()->constrained('destrezas_curriculares')->restrictOnDelete();
            $table->foreignId('indicador_evaluacion_id')->nullable()->constrained('indicadores_evaluacion')->restrictOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->string('etiqueta', 40);
            $table->string('titulo', 500);
            $table->string('codigo_destreza_fuente', 60);
            $table->string('codigo_indicador_fuente', 60);
            $table->unsignedSmallInteger('pagina_inicio')->nullable();
            $table->unsignedSmallInteger('pagina_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['unidad_libro_id', 'orden'], 'tema_libro_orden_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temas_libro');
        Schema::dropIfExists('objetivo_unidad_libro');
        Schema::dropIfExists('unidades_libro');
        Schema::dropIfExists('libros_texto');
    }
};
