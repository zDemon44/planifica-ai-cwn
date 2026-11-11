<?php

namespace Database\Seeders;

use App\Models\Materia;
use App\Services\ImportarLibroTextoExcel;
use Illuminate\Database\Seeder;

class LibroMatematicaCuartoSeeder extends Seeder
{
    public function __construct(private ImportarLibroTextoExcel $importador) {}

    public function run(): void
    {
        $materia = Materia::query()->whereIn('nombre', ['Matemática', 'Matemáticas'])->first();
        $ruta = base_path('Datos libro matematicas 4to.xlsx');
        if (! $materia || ! is_file($ruta)) {
            return;
        }

        $this->importador->handle(
            $materia,
            $ruta,
            'matematica-4to',
            'Libro de Matemática 4.º EGB',
            4,
        );
    }
}
