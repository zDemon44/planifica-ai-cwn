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
        $this->desplazar([4, 3, 2, 1], 1);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->desplazar([2, 3, 4, 5], -1);
    }

    /** @param array<int, int> $grupos */
    private function desplazar(array $grupos, int $incremento): void
    {
        $materiaId = DB::table('materias')->where('nombre', 'Matemática')->value('id')
            ?? DB::table('materias')->where('nombre', 'Matemáticas')->value('id');
        if (! $materiaId) {
            return;
        }

        DB::transaction(function () use ($materiaId, $grupos, $incremento): void {
            foreach (['destrezas_curriculares', 'indicadores_evaluacion'] as $tabla) {
                foreach ($grupos as $grupo) {
                    DB::table($tabla)
                        ->where('materia_id', $materiaId)
                        ->where('grupo', $grupo)
                        ->update(['grupo' => $grupo + $incremento]);
                }
            }
        });
    }
};
