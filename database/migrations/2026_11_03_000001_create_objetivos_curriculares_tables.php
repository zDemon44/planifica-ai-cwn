<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objetivos_curriculares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->string('subnivel', 40);
            $table->string('codigo', 60);
            $table->longText('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['materia_id', 'subnivel', 'codigo'], 'objetivo_materia_subnivel_codigo_unique');
        });

        Schema::create('objetivo_planificacion', function (Blueprint $table): void {
            $table->foreignId('objetivo_curricular_id')->constrained('objetivos_curriculares')->restrictOnDelete();
            $table->foreignId('planificacion_id')->constrained('planificaciones')->cascadeOnDelete();
            $table->primary(['objetivo_curricular_id', 'planificacion_id'], 'objetivo_planificacion_primary');
        });

        $materiaId = DB::table('materias')->whereIn('nombre', ['Matemática', 'Matemáticas'])->value('id');
        if (! $materiaId) {
            return;
        }

        $ahora = now();
        DB::table('objetivos_curriculares')->insertOrIgnore(collect($this->objetivosMatematica())
            ->map(fn (string $descripcion, string $codigo): array => [
                'materia_id' => $materiaId,
                'subnivel' => 'basica_elemental',
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('objetivo_planificacion');
        Schema::dropIfExists('objetivos_curriculares');
    }

    /** @return array<string, string> */
    private function objetivosMatematica(): array
    {
        return [
            'O.M.2.1.' => 'Explicar y construir patrones de figuras y numéricos relacionándolos con la suma, la resta y la multiplicación, para desarrollar el pensamiento lógico matemático.',
            'O.M.2.2.' => 'Utilizar objetos del entorno para formar conjuntos, establecer gráficamente la correspondencia entre sus elementos y desarrollar la comprensión de modelos matemáticos.',
            'O.M.2.3.' => 'Integrar concretamente el concepto de número, y reconocer situaciones del entorno en las que se presenten problemas que requieran la formulación de expresiones matemáticas sencillas, para resolverlas, de forma individual o grupal, utilizando los algoritmos de adición, sustracción, multiplicación y división exacta.',
            'O.M.2.4.' => 'Aplicar estrategias de conteo, procedimientos de cálculos de suma, resta, multiplicación y divisiones del 0 al 9 999, para resolver de forma colaborativa problemas cotidianos de su entorno.',
            'O.M.2.5.' => 'Comprender el espacio que lo rodea, valorar lugares históricos, turísticos y bienes naturales, identificando como conceptos matemáticos los elementos y propiedades de cuerpos y figuras geométricas en objetos del entorno.',
            'O.M.2.6.' => 'Resolver situaciones cotidianas que impliquen la medición, estimación y el cálculo de longitudes, capacidades y masas, con unidades convencionales y no convencionales de objetos de su entorno, para una mejor comprensión del espacio que le rodea, la valoración de su tiempo y el de los otros, y el fomento de la honestidad e integridad en sus actos.',
            'O.M.2.7.' => 'Participar en proyectos de análisis de información del entorno inmediato, mediante la recolección y representación de datos estadísticos en pictogramas y diagramas de barras; potenciando, así, el pensamiento lógico-matemático y creativo, al interpretar la información y expresar conclusiones asumiendo compromisos.',
        ];
    }
};
