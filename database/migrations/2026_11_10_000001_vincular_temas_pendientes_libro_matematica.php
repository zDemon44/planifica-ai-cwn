<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $temas = DB::table('temas_libro as tema')
                ->join('unidades_libro as unidad', 'unidad.id', '=', 'tema.unidad_libro_id')
                ->join('libros_texto as libro', 'libro.id', '=', 'unidad.libro_texto_id')
                ->where('libro.codigo', 'matematica-4to')
                ->where('tema.activo', true)
                ->where(function ($consulta): void {
                    $consulta->whereNull('tema.destreza_curricular_id')
                        ->orWhereNull('tema.indicador_evaluacion_id');
                })
                ->get([
                    'tema.id', 'libro.materia_id', 'libro.grado',
                    'tema.codigo_destreza_fuente', 'tema.codigo_indicador_fuente',
                ]);

            foreach ($temas as $tema) {
                $relacion = DB::table('destreza_indicador as relacion')
                    ->join('destrezas_curriculares as destreza', 'destreza.id', '=', 'relacion.destreza_curricular_id')
                    ->join('indicadores_evaluacion as indicador', 'indicador.id', '=', 'relacion.indicador_evaluacion_id')
                    ->where('destreza.materia_id', $tema->materia_id)
                    ->where('destreza.grado', $tema->grado)
                    ->where('destreza.codigo', $tema->codigo_destreza_fuente)
                    ->where('destreza.activo', true)
                    ->where('indicador.materia_id', $tema->materia_id)
                    ->where('indicador.grado', $tema->grado)
                    ->where('indicador.activo', true)
                    ->whereColumn('destreza.grupo', 'indicador.grupo')
                    ->whereRaw("REPLACE(indicador.codigo, 'Ref.', '') = ?", [$tema->codigo_indicador_fuente])
                    ->orderByRaw('CASE WHEN indicador.codigo = ? THEN 0 ELSE 1 END', [$tema->codigo_indicador_fuente])
                    ->first([
                        'destreza.id as destreza_curricular_id',
                        'indicador.id as indicador_evaluacion_id',
                    ]);

                if ($relacion) {
                    DB::table('temas_libro')->where('id', $tema->id)->update([
                        'destreza_curricular_id' => $relacion->destreza_curricular_id,
                        'indicador_evaluacion_id' => $relacion->indicador_evaluacion_id,
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('temas_libro as tema')
            ->join('unidades_libro as unidad', 'unidad.id', '=', 'tema.unidad_libro_id')
            ->join('libros_texto as libro', 'libro.id', '=', 'unidad.libro_texto_id')
            ->where('libro.codigo', 'matematica-4to')
            ->where(function ($consulta): void {
                foreach ($this->temasVinculados() as [$unidad, $orden]) {
                    $consulta->orWhere(function ($tema) use ($unidad, $orden): void {
                        $tema->where('unidad.numero', $unidad)->where('tema.orden', $orden);
                    });
                }
            })
            ->update([
                'tema.destreza_curricular_id' => null,
                'tema.indicador_evaluacion_id' => null,
                'tema.updated_at' => now(),
            ]);
    }

    /** @return array<int, array{int, int}> */
    private function temasVinculados(): array
    {
        return [
            [1, 1], [1, 2], [1, 3], [1, 4],
            [2, 10],
            [5, 1], [5, 2], [5, 3], [5, 4], [5, 5],
            [6, 1],
        ];
    }
};
