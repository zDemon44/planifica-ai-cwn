<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLAS = [
        'courses' => 'cursos',
        'academic_periods' => 'periodos_academicos',
        'sections' => 'secciones',
    ];

    private const COLUMNAS = [
        'cursos' => ['name' => 'nombre', 'position' => 'orden', 'is_final' => 'es_final'],
        'periodos_academicos' => ['name' => 'nombre', 'starts_on' => 'fecha_inicio', 'ends_on' => 'fecha_fin', 'is_closed' => 'cerrado'],
        'secciones' => ['course_id' => 'curso_id', 'academic_period_id' => 'periodo_academico_id', 'name' => 'nombre'],
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $anterior => $nuevo) {
            $this->renombrarTabla($anterior, $nuevo);
        }
        foreach (self::COLUMNAS as $tabla => $columnas) {
            foreach ($columnas as $anterior => $nuevo) {
                $this->renombrarColumna($tabla, $anterior, $nuevo);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::COLUMNAS, true) as $tabla => $columnas) {
            foreach (array_reverse($columnas, true) as $anterior => $nuevo) {
                $this->renombrarColumna($tabla, $nuevo, $anterior);
            }
        }
        foreach (array_reverse(self::TABLAS, true) as $anterior => $nuevo) {
            $this->renombrarTabla($nuevo, $anterior);
        }
    }

    private function renombrarTabla(string $anterior, string $nuevo): void
    {
        if (! Schema::hasTable($anterior) && Schema::hasTable($nuevo)) {
            return;
        }
        Schema::rename($anterior, $nuevo);
    }

    private function renombrarColumna(string $tabla, string $anterior, string $nuevo): void
    {
        if (! Schema::hasColumn($tabla, $anterior) && Schema::hasColumn($tabla, $nuevo)) {
            return;
        }
        try {
            Schema::table($tabla, function (Blueprint $table) use ($anterior, $nuevo) {
                $table->renameColumn($anterior, $nuevo);
            });
        } catch (QueryException $exception) {
            // MySQL puede reportar 1146 después de aplicar un cambio con referencias; comprobar el esquema antes de continuar.
            if (($exception->errorInfo[1] ?? null) !== 1146 || Schema::hasColumn($tabla, $anterior) || ! Schema::hasColumn($tabla, $nuevo)) {
                throw $exception;
            }
        }
    }
};
