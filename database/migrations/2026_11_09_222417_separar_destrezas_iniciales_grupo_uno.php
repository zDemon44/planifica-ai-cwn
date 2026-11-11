<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $destrezaIds = $this->destrezasObjetivo()->pluck('destrezas_curriculares.id');

            DB::table('destreza_indicador')->whereIn('destreza_curricular_id', $destrezaIds)->delete();
            DB::table('destrezas_curriculares')->whereIn('id', $destrezaIds)->update(['grupo' => 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $destrezas = $this->destrezasObjetivo()->get([
                'destrezas_curriculares.id',
                'destrezas_curriculares.materia_id',
                'destrezas_curriculares.grado',
            ]);

            DB::table('destrezas_curriculares')->whereIn('id', $destrezas->pluck('id'))->update(['grupo' => 2]);

            foreach ($destrezas as $destreza) {
                $relaciones = DB::table('indicadores_evaluacion')
                    ->where('materia_id', $destreza->materia_id)
                    ->where('grado', $destreza->grado)
                    ->where('grupo', 2)
                    ->where('activo', true)
                    ->pluck('id')
                    ->map(fn (int $indicadorId): array => [
                        'destreza_curricular_id' => $destreza->id,
                        'indicador_evaluacion_id' => $indicadorId,
                    ])->all();

                if ($relaciones !== []) {
                    DB::table('destreza_indicador')->insertOrIgnore($relaciones);
                }
            }
        });
    }

    private function destrezasObjetivo(): Builder
    {
        return DB::table('destrezas_curriculares')
            ->join('curriculos', 'curriculos.id', '=', 'destrezas_curriculares.curriculo_id')
            ->where('curriculos.codigo', 'obligatorio-elemental')
            ->whereIn('destrezas_curriculares.codigo', ['M.2.1.8', 'M.2.1.10'])
            ->whereIn('destrezas_curriculares.grado', [2, 3, 4]);
    }
};
