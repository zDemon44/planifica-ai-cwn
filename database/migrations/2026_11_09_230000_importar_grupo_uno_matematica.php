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
        $curriculoActualId = DB::table('curriculos')->where('codigo', 'priorizado-2025')->value('id');
        $curriculoAnteriorId = DB::table('curriculos')->where('codigo', 'obligatorio-elemental')->value('id');

        if (! $materiaId || ! $curriculoActualId || ! $curriculoAnteriorId) {
            return;
        }

        DB::transaction(function () use ($materiaId, $curriculoActualId, $curriculoAnteriorId): void {
            $ahora = now();

            foreach ($this->destrezas() as $codigo => $datos) {
                foreach ($datos['grados'] as $grado => $descripcion) {
                    DB::table('destrezas_curriculares')->updateOrInsert(
                        [
                            'curriculo_id' => $curriculoActualId,
                            'materia_id' => $materiaId,
                            'grado' => $grado,
                            'codigo' => $codigo,
                        ],
                        [
                            'descripcion' => $descripcion,
                            'grupo' => 1,
                            'activo' => true,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ]
                    );

                    $destrezaId = DB::table('destrezas_curriculares')
                        ->where('curriculo_id', $curriculoActualId)
                        ->where('materia_id', $materiaId)
                        ->where('grado', $grado)
                        ->where('codigo', $codigo)
                        ->value('id');

                    DB::table('competencia_destreza')->where('destreza_curricular_id', $destrezaId)->delete();

                    foreach ($datos['competencias'] as $competencia) {
                        $competenciaId = DB::table('competencias_curriculares')->where('codigo', $competencia)->value('id')
                            ?? DB::table('competencias_curriculares')->insertGetId([
                                'codigo' => $competencia,
                                'created_at' => $ahora,
                                'updated_at' => $ahora,
                            ]);

                        DB::table('competencia_destreza')->insertOrIgnore([
                            'competencia_curricular_id' => $competenciaId,
                            'destreza_curricular_id' => $destrezaId,
                        ]);
                    }
                }
            }

            foreach ($this->indicadores() as $codigo => $descripciones) {
                foreach ($descripciones as $grado => $descripcion) {
                    DB::table('indicadores_evaluacion')->updateOrInsert(
                        [
                            'curriculo_id' => $curriculoActualId,
                            'materia_id' => $materiaId,
                            'grado' => $grado,
                            'codigo' => $codigo,
                        ],
                        [
                            'descripcion' => $descripcion,
                            'grupo' => 1,
                            'activo' => true,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ]
                    );
                }
            }

            $destrezasActuales = DB::table('destrezas_curriculares')
                ->where('curriculo_id', $curriculoActualId)
                ->where('materia_id', $materiaId)
                ->whereIn('grado', [2, 3, 4])
                ->whereIn('codigo', array_keys($this->destrezas()))
                ->get(['id', 'grado', 'codigo']);

            $destrezasAnteriores = DB::table('destrezas_curriculares')
                ->where('curriculo_id', $curriculoAnteriorId)
                ->where('materia_id', $materiaId)
                ->whereIn('grado', [2, 3, 4])
                ->whereIn('codigo', ['M.2.1.8', 'M.2.1.10'])
                ->get(['id', 'grado', 'codigo']);

            $destrezas = $destrezasActuales->concat($destrezasAnteriores);

            DB::table('destreza_indicador')->whereIn('destreza_curricular_id', $destrezas->pluck('id'))->delete();

            $indicadores = DB::table('indicadores_evaluacion')
                ->where('curriculo_id', $curriculoActualId)
                ->where('materia_id', $materiaId)
                ->whereIn('grado', [2, 3, 4])
                ->whereIn('codigo', array_keys($this->indicadores()))
                ->get(['id', 'grado', 'codigo'])
                ->groupBy('grado');

            foreach ($destrezas as $destreza) {
                foreach ($indicadores->get($destreza->grado, collect()) as $indicador) {
                    DB::table('destreza_indicador')->insertOrIgnore([
                        'destreza_curricular_id' => $destreza->id,
                        'indicador_evaluacion_id' => $indicador->id,
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
        $materiaId = DB::table('materias')->whereIn('nombre', ['Matemática', 'Matemáticas'])->value('id');
        $curriculoActualId = DB::table('curriculos')->where('codigo', 'priorizado-2025')->value('id');

        if (! $materiaId || ! $curriculoActualId) {
            return;
        }

        DB::transaction(function () use ($materiaId, $curriculoActualId): void {
            $destrezaIds = DB::table('destrezas_curriculares')
                ->where('curriculo_id', $curriculoActualId)
                ->where('materia_id', $materiaId)
                ->whereIn('grado', [2, 3, 4])
                ->whereIn('codigo', array_keys($this->destrezas()))
                ->pluck('id');

            $indicadorIds = DB::table('indicadores_evaluacion')
                ->where('curriculo_id', $curriculoActualId)
                ->where('materia_id', $materiaId)
                ->whereIn('grado', [2, 3, 4])
                ->whereIn('codigo', array_keys($this->indicadores()))
                ->pluck('id');

            DB::table('destreza_indicador')
                ->whereIn('destreza_curricular_id', $destrezaIds)
                ->orWhereIn('indicador_evaluacion_id', $indicadorIds)
                ->delete();

            DB::table('destrezas_curriculares')->whereIn('id', $destrezaIds)->delete();
            DB::table('indicadores_evaluacion')->whereIn('id', $indicadorIds)->delete();
        });
    }

    /**
     * @return array<string, array{competencias: array<int, string>, grados: array<int, string>}>
     */
    private function destrezas(): array
    {
        return [
            'M.2.1.1' => [
                'competencias' => ['ES'],
                'grados' => [
                    2 => 'Representar gráficamente conjuntos de objetos del entorno, agrupándolos por una propiedad común e identificando los elementos que pertenecen o no al conjunto. (Ref. M.2.1.1.)',
                    3 => 'Representar gráficamente conjuntos y subconjuntos de objetos, discriminando una o más propiedades o atributos. (Ref. M.2.1.1.)',
                    4 => 'M.2.1.1. Representar gráficamente conjuntos y subconjuntos, discriminando las propiedades o atributos de los objetos.',
                ],
            ],
            'M.2.1.2' => [
                'competencias' => ['C'],
                'grados' => [
                    2 => 'Describir y reproducir patrones sencillos de objetos y figuras basados en un atributo, como color, forma o tamaño. (Ref. M.2.1.2.)',
                    3 => 'Describir y reproducir patrones de objetos y figuras basados en uno o dos atributos. (Ref. M.2.1.2.)',
                    4 => 'M.2.1.2. Describir y reproducir patrones de objetos y figuras basándose en sus atributos.',
                ],
            ],
            'M.2.1.3' => [
                'competencias' => ['CM'],
                'grados' => [
                    2 => 'Describir y reproducir patrones numéricos de suma y resta con números naturales hasta 99, contando hacia adelante y hacia atrás. (Ref. M.2.1.3.)',
                    3 => 'Describir y reproducir patrones numéricos basados en sumas y restas con números naturales hasta 999, contando hacia adelante y hacia atrás. (Ref. M.2.1.3.)',
                    4 => 'M.2.1.3. Describir y reproducir patrones numéricos basados en sumas y restas, contando hacia adelante y hacia atrás.',
                ],
            ],
            'M.2.1.4' => [
                'competencias' => [],
                'grados' => [
                    2 => 'Describir y reproducir patrones numéricos crecientes mediante sumas reiteradas con números naturales hasta 99. (Ref. M.2.1.4.)',
                    3 => 'Describir y reproducir patrones numéricos crecientes mediante la suma y la multiplicación con números naturales hasta 999. (Ref. M.2.1.4.)',
                    4 => 'M.2.1.4. Describir y reproducir patrones numéricos crecientes con la suma y la multiplicación.',
                ],
            ],
            'M.2.1.5' => [
                'competencias' => ['CM'],
                'grados' => [
                    2 => 'Construir patrones de figuras basados en un atributo y patrones numéricos sencillos a partir de la suma y la resta. (Ref. M.2.1.5.)',
                    3 => 'Construir patrones de figuras basados en uno o dos atributos y patrones numéricos a partir de la suma, la resta y multiplicaciones sencillas. (Ref. M.2.1.5.)',
                    4 => 'M.2.1.5. Construir patrones de figuras basándose en sus atributos y patrones numéricos a partir de la suma, resta y multiplicación.',
                ],
            ],
            'M.2.1.6' => [
                'competencias' => ['ES'],
                'grados' => [
                    2 => 'Relacionar, mediante correspondencia uno a uno, elementos de un conjunto de salida con elementos de un conjunto de llegada, utilizando objetos o dibujos. (Ref. M.2.1.6.)',
                    3 => 'Relacionar los elementos de un conjunto de salida con los de un conjunto de llegada, a partir de correspondencias representadas con diagramas. (Ref. M.2.1.6.)',
                    4 => 'M.2.1.6. Relacionar los elementos del conjunto de salida con los elementos del conjunto de llegada, a partir de la correspondencia entre elementos.',
                ],
            ],
            'M.2.1.7' => [
                'competencias' => ['CM'],
                'grados' => [
                    2 => 'Representar parejas ordenadas sencillas de una correspondencia uno a uno entre los elementos de dos conjuntos, mediante diagramas y tablas. (Ref. M.2.1.7.)',
                    3 => 'Representar, en diagramas, tablas y una cuadrícula, parejas ordenadas de una relación entre los elementos de un conjunto de salida y uno de llegada. (Ref. M.2.1.7.)',
                    4 => 'M.2.1.7. Representar, en diagramas, tablas y una cuadrícula, las parejas ordenadas de una relación específica entre los elementos del conjunto de salida y los elementos del conjunto de llegada.',
                ],
            ],
            'M.2.1.9' => [
                'competencias' => [],
                'grados' => [
                    2 => 'Representar por extensión pares ordenados sencillos obtenidos de la correspondencia entre elementos de dos conjuntos, con apoyo concreto o gráfico. (Ref. M.2.1.9.)',
                    3 => 'Representar por extensión y gráficamente pares ordenados del producto cartesiano AxB de conjuntos pequeños. (Ref. M.2.1.9.)',
                    4 => 'M.2.1.9. Representar por extensión y gráficamente los pares ordenados del producto cartesiano AxB.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function indicadores(): array
    {
        return [
            'I.M.2.1.1' => [
                2 => 'Discrimina una propiedad de los objetos, forma conjuntos y obtiene subconjuntos sencillos de un conjunto universo, con apoyo concreto o gráfico. (Ref. I.M.2.1.1.) (S.2.)',
                3 => 'Discrimina una o más propiedades de los objetos y obtiene subconjuntos de un conjunto universo, mediante representaciones gráficas. (Ref. I.M.2.1.1.) (S.2.)',
                4 => 'I.M.2.1.1. Discrimina propiedades de los objetos y obtiene subconjuntos de un conjunto universo. (S.2.)',
            ],
            'I.M.2.1.2' => [
                2 => 'Propone y reproduce patrones sencillos de objetos y figuras según un atributo, y completa secuencias numéricas de suma o resta con apoyo concreto. (Ref. I.M.2.1.2.) (I.1.)',
                3 => 'Propone patrones y construye series de objetos y figuras según sus atributos, y secuencias numéricas basadas en sumas y restas. (Ref. I.M.2.1.2.) (I.1.)',
                4 => 'I.M.2.1.2. Propone patrones y construye series de objetos, figuras y secuencias numéricas. (I.1.)',
            ],
            'I.M.2.1.3' => [
                2 => 'Identifica y representa correspondencias uno a uno entre elementos de dos conjuntos mediante objetos, diagramas o tablas, y forma pares ordenados sencillos. (Ref. I.M.2.1.3.) (I.3., I.4.)',
                3 => 'Discrimina, en diagramas, tablas y una cuadrícula, pares ordenados del producto cartesiano AxB que cumplen una relación uno a uno. (Ref. I.M.2.1.3.) (I.3., I.4.)',
                4 => 'I.M.2.1.3. Discrimina en diagramas, tablas y una cuadrícula los pares ordenados del producto cartesiano AxB que cumplen una relación uno a uno. (I.3., I.4.)',
            ],
        ];
    }
};
