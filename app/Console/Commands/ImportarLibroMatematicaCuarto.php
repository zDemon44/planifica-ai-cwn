<?php

namespace App\Console\Commands;

use App\Models\Materia;
use App\Services\ImportarLibroTextoExcel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('libros:importar-matematica-cuarto {archivo=Datos libro matematicas 4to.xlsx : Ruta del archivo Excel}')]
#[Description('Importa las unidades, objetivos y temas del libro de Matemática de cuarto grado')]
class ImportarLibroMatematicaCuarto extends Command
{
    public function handle(ImportarLibroTextoExcel $importador): int
    {
        $rutaRecibida = (string) $this->argument('archivo');
        $ruta = is_file($rutaRecibida) ? $rutaRecibida : base_path($rutaRecibida);
        if (! is_file($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");

            return self::FAILURE;
        }

        $materia = Materia::query()->whereIn('nombre', ['Matemática', 'Matemáticas'])->first();
        if (! $materia) {
            $this->error('No existe la materia Matemática.');

            return self::FAILURE;
        }

        $resultado = $importador->handle(
            $materia,
            $ruta,
            'matematica-4to',
            'Libro de Matemática 4.º EGB',
            4,
        );

        $this->info('Libro importado correctamente.');
        $this->table(['Dato', 'Total'], [
            ['Unidades', $resultado['unidades']],
            ['Objetivos relacionados', $resultado['objetivos']],
            ['Temas', $resultado['temas']],
            ['Temas completamente vinculados', $resultado['temas_completos']],
            ['Temas pendientes de catálogo', $resultado['temas_pendientes']],
        ]);

        return self::SUCCESS;
    }
}
