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
        $materiaId = DB::table('materias')->whereIn('nombre', ['Matemática', 'Matemáticas'])->value('id');
        $curriculoId = DB::table('curriculos')->where('codigo', 'obligatorio-elemental')->value('id');

        if (! $materiaId || ! $curriculoId) {
            return;
        }

        DB::transaction(function () use ($materiaId, $curriculoId): void {
            $ahora = now();

            foreach ($this->descripciones() as $grado => $descripcion) {
                DB::table('destrezas_curriculares')->updateOrInsert(
                    [
                        'materia_id' => $materiaId,
                        'curriculo_id' => $curriculoId,
                        'grado' => $grado,
                        'codigo' => 'M.2.2.18',
                    ],
                    [
                        'descripcion' => $descripcion,
                        'grupo' => 4,
                        'activo' => true,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]
                );
            }

            $destrezas = DB::table('destrezas_curriculares')
                ->where('materia_id', $materiaId)
                ->where('curriculo_id', $curriculoId)
                ->where('codigo', 'M.2.2.18')
                ->whereIn('grado', [2, 3, 4])
                ->get(['id', 'grado']);

            DB::table('destreza_indicador')->whereIn('destreza_curricular_id', $destrezas->pluck('id'))->delete();

            foreach ($destrezas as $destreza) {
                $relaciones = DB::table('indicadores_evaluacion')
                    ->where('materia_id', $materiaId)
                    ->where('grado', $destreza->grado)
                    ->where('grupo', 4)
                    ->where('activo', true)
                    ->pluck('id')
                    ->map(fn (int $indicadorId): array => [
                        'destreza_curricular_id' => $destreza->id,
                        'indicador_evaluacion_id' => $indicadorId,
                    ])->all();

                DB::table('destreza_indicador')->insertOrIgnore($relaciones);
            }

            $destrezaCuartoId = $destrezas->firstWhere('grado', 4)?->id;
            $indicadorCuartoId = DB::table('indicadores_evaluacion')
                ->where('materia_id', $materiaId)
                ->where('grado', 4)
                ->where('codigo', 'I.M.2.4.3')
                ->where('activo', true)
                ->whereExists(function ($consulta) use ($destrezaCuartoId): void {
                    $consulta->selectRaw('1')
                        ->from('destreza_indicador')
                        ->whereColumn('destreza_indicador.indicador_evaluacion_id', 'indicadores_evaluacion.id')
                        ->where('destreza_indicador.destreza_curricular_id', $destrezaCuartoId);
                })
                ->value('id');

            if ($destrezaCuartoId && $indicadorCuartoId) {
                DB::table('temas_libro as tema')
                    ->join('unidades_libro as unidad', 'unidad.id', '=', 'tema.unidad_libro_id')
                    ->join('libros_texto as libro', 'libro.id', '=', 'unidad.libro_texto_id')
                    ->where('libro.codigo', 'matematica-4to')
                    ->where('tema.codigo_destreza_fuente', 'M.2.2.18')
                    ->where('tema.codigo_indicador_fuente', 'I.M.2.4.3')
                    ->update([
                        'tema.destreza_curricular_id' => $destrezaCuartoId,
                        'tema.indicador_evaluacion_id' => $indicadorCuartoId,
                        'tema.updated_at' => $ahora,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $materiaId = DB::table('materias')->whereIn('nombre', ['Matemática', 'Matemáticas'])->value('id');
        $curriculoId = DB::table('curriculos')->where('codigo', 'obligatorio-elemental')->value('id');

        if (! $materiaId || ! $curriculoId) {
            return;
        }

        DB::transaction(function () use ($materiaId, $curriculoId): void {
            $destrezaIds = DB::table('destrezas_curriculares')
                ->where('materia_id', $materiaId)
                ->where('curriculo_id', $curriculoId)
                ->where('codigo', 'M.2.2.18')
                ->whereIn('grado', [2, 3, 4])
                ->pluck('id');

            DB::table('temas_libro')->whereIn('destreza_curricular_id', $destrezaIds)->update([
                'destreza_curricular_id' => null,
                'indicador_evaluacion_id' => null,
                'updated_at' => now(),
            ]);
            DB::table('destreza_indicador')->whereIn('destreza_curricular_id', $destrezaIds)->delete();
            DB::table('destrezas_curriculares')->whereIn('id', $destrezaIds)->delete();
        });
    }

    /** @return array<int, string> */
    private function descripciones(): array
    {
        return [
            2 => 'Leer horas en punto y medias horas en un reloj analógico, relacionándolas con actividades cotidianas. (Ref. M.2.2.18.)',
            3 => 'Leer horas y minutos en intervalos de cinco minutos en un reloj analógico, y relacionarlos con situaciones cotidianas. (Ref. M.2.2.18.)',
            4 => 'M.2.2.18. Leer horas y minutos en un reloj analógico.',
        ];
    }
};
