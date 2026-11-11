<?php

namespace App\Services;

use App\Models\DestrezaCurricular;
use App\Models\IndicadorEvaluacion;
use App\Models\LibroTexto;
use App\Models\Materia;
use App\Models\ObjetivoCurricular;
use App\Models\TemaLibro;
use App\Models\UnidadLibro;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportarLibroTextoExcel
{
    public function __construct(private LectorPrimeraHojaExcel $lectorExcel) {}

    /**
     * @return array{libro_id:int,unidades:int,objetivos:int,temas:int,temas_completos:int,temas_pendientes:int,destrezas_vinculadas:int,indicadores_vinculados:int}
     */
    public function handle(
        Materia $materia,
        UploadedFile|string $archivo,
        string $codigoLibro,
        string $tituloLibro,
        int $grado,
    ): array {
        $ruta = $archivo instanceof UploadedFile ? $archivo->getRealPath() : $archivo;
        $nombreArchivo = $archivo instanceof UploadedFile
            ? $archivo->getClientOriginalName()
            : basename(str_replace('\\', '/', $archivo));
        $unidades = $this->interpretar($this->lectorExcel->leer((string) $ruta));
        $objetivos = $this->objetivosPorCodigo($materia);
        $this->validarObjetivos($unidades, $objetivos);

        $destrezas = DestrezaCurricular::query()
            ->with(['curriculo', 'indicadores.curriculo'])
            ->whereBelongsTo($materia)
            ->where('grado', $grado)
            ->where('activo', true)
            ->get()
            ->groupBy('codigo');
        $indicadores = IndicadorEvaluacion::query()
            ->with('curriculo')
            ->whereBelongsTo($materia)
            ->where('grado', $grado)
            ->where('activo', true)
            ->get();

        return DB::transaction(function () use (
            $materia,
            $codigoLibro,
            $tituloLibro,
            $grado,
            $nombreArchivo,
            $unidades,
            $objetivos,
            $destrezas,
            $indicadores,
        ): array {
            $libro = LibroTexto::updateOrCreate(
                ['codigo' => $codigoLibro],
                [
                    'materia_id' => $materia->id,
                    'grado' => $grado,
                    'titulo' => $tituloLibro,
                    'archivo_origen' => $nombreArchivo,
                    'activo' => true,
                ]
            );
            $unidadesImportadas = [];
            $temasImportados = [];
            $objetivosRelacionados = 0;
            $destrezasVinculadas = 0;
            $indicadoresVinculados = 0;
            $temasCompletos = 0;

            foreach ($unidades as $numero => $datosUnidad) {
                $unidad = UnidadLibro::updateOrCreate(
                    ['libro_texto_id' => $libro->id, 'numero' => $numero],
                    ['titulo' => $datosUnidad['titulo'], 'activo' => true]
                );
                $unidadesImportadas[] = $unidad->id;
                $objetivoIds = collect($datosUnidad['objetivos'])
                    ->map(fn (string $codigo): int => $objetivos[$this->normalizarCodigoObjetivo($codigo)]->id)
                    ->unique()
                    ->values()
                    ->all();
                $unidad->objetivos()->sync($objetivoIds);
                $objetivosRelacionados += count($objetivoIds);
                $temasUnidad = [];

                foreach ($datosUnidad['temas'] as $orden => $datosTema) {
                    [$destreza, $indicador] = $this->resolverReferenciasCurriculares(
                        $destrezas->get($datosTema['codigo_destreza'], new EloquentCollection),
                        $indicadores,
                        $datosTema['codigo_indicador'],
                    );
                    $tema = TemaLibro::updateOrCreate(
                        ['unidad_libro_id' => $unidad->id, 'orden' => $orden],
                        [
                            'destreza_curricular_id' => $destreza?->id,
                            'indicador_evaluacion_id' => $indicador?->id,
                            'etiqueta' => $datosTema['etiqueta'],
                            'titulo' => $datosTema['titulo'],
                            'codigo_destreza_fuente' => $datosTema['codigo_destreza'],
                            'codigo_indicador_fuente' => $datosTema['codigo_indicador'],
                            'activo' => true,
                        ]
                    );
                    $temasUnidad[] = $tema->id;
                    $temasImportados[] = $tema->id;
                    $destrezasVinculadas += $destreza ? 1 : 0;
                    $indicadoresVinculados += $indicador ? 1 : 0;
                    $temasCompletos += $destreza && $indicador ? 1 : 0;
                }

                $unidad->temas()->whereNotIn('id', $temasUnidad)->update(['activo' => false]);
            }

            $unidadesInactivas = $libro->unidades()->whereNotIn('id', $unidadesImportadas)->get();
            if ($unidadesInactivas->isNotEmpty()) {
                TemaLibro::whereIn('unidad_libro_id', $unidadesInactivas->modelKeys())->update(['activo' => false]);
                $unidadesInactivas->toQuery()->update(['activo' => false]);
            }

            return [
                'libro_id' => $libro->id,
                'unidades' => count($unidadesImportadas),
                'objetivos' => $objetivosRelacionados,
                'temas' => count($temasImportados),
                'temas_completos' => $temasCompletos,
                'temas_pendientes' => count($temasImportados) - $temasCompletos,
                'destrezas_vinculadas' => $destrezasVinculadas,
                'indicadores_vinculados' => $indicadoresVinculados,
            ];
        }, 3);
    }

    /**
     * @param  array<int, array<string, string>>  $filas
     * @return array<int, array{titulo:string,objetivos:array<int,string>,temas:array<int,array{etiqueta:string,titulo:string,codigo_destreza:string,codigo_indicador:string}>}>
     */
    private function interpretar(array $filas): array
    {
        $unidades = [];
        $numeroUnidad = null;

        foreach ($filas as $fila) {
            $columnaA = trim($fila['A'] ?? '');
            if (preg_match('/^Unidad\s+(\d+)/iu', $columnaA, $coincidencia)) {
                $numeroUnidad = (int) $coincidencia[1];
                $unidades[$numeroUnidad] = ['titulo' => '', 'objetivos' => [], 'temas' => []];

                continue;
            }
            if ($numeroUnidad === null) {
                continue;
            }

            if ($this->esFilaObjetivos($fila)) {
                foreach (['D', 'E'] as $columna) {
                    $codigo = trim($fila[$columna] ?? '');
                    if ($codigo !== '') {
                        $unidades[$numeroUnidad]['objetivos'][] = $codigo;
                    }
                }

                continue;
            }
            if (mb_strtolower($columnaA) === 'titulo de la unidad') {
                $unidades[$numeroUnidad]['titulo'] = trim($fila['B'] ?? '');

                continue;
            }
            if ($this->esFilaTema($fila)) {
                $orden = count($unidades[$numeroUnidad]['temas']) + 1;
                $unidades[$numeroUnidad]['temas'][$orden] = [
                    'etiqueta' => trim($fila['B']),
                    'titulo' => trim($fila['C']),
                    'codigo_destreza' => trim($fila['D']),
                    'codigo_indicador' => trim($fila['E']),
                ];
            }
        }

        if ($unidades === []) {
            throw ValidationException::withMessages(['archivo' => 'El Excel no contiene unidades reconocibles.']);
        }
        foreach ($unidades as $numero => $unidad) {
            if ($unidad['titulo'] === '' || $unidad['objetivos'] === [] || $unidad['temas'] === []) {
                throw ValidationException::withMessages([
                    'archivo' => "La unidad {$numero} debe incluir título, objetivos y al menos un tema.",
                ]);
            }
        }

        return $unidades;
    }

    /** @param array<string, string> $fila */
    private function esFilaObjetivos(array $fila): bool
    {
        return str_starts_with($this->normalizarCodigoObjetivo($fila['D'] ?? ''), 'O.M.');
    }

    /** @param array<string, string> $fila */
    private function esFilaTema(array $fila): bool
    {
        return collect(['B', 'C', 'D', 'E'])->every(fn (string $columna): bool => trim($fila[$columna] ?? '') !== '');
    }

    /** @return Collection<string, ObjetivoCurricular> */
    private function objetivosPorCodigo(Materia $materia): Collection
    {
        return ObjetivoCurricular::query()
            ->whereBelongsTo($materia)
            ->where('activo', true)
            ->get()
            ->keyBy(fn (ObjetivoCurricular $objetivo): string => $this->normalizarCodigoObjetivo($objetivo->codigo));
    }

    /**
     * @param  array<int, array{titulo:string,objetivos:array<int,string>,temas:array<int,mixed>}>  $unidades
     * @param  Collection<string, ObjetivoCurricular>  $objetivos
     */
    private function validarObjetivos(array $unidades, Collection $objetivos): void
    {
        $faltantes = collect($unidades)
            ->flatMap(fn (array $unidad): array => $unidad['objetivos'])
            ->map(fn (string $codigo): string => $this->normalizarCodigoObjetivo($codigo))
            ->unique()
            ->reject(fn (string $codigo): bool => $objetivos->has($codigo))
            ->values();
        if ($faltantes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'archivo' => 'No existen los siguientes objetivos curriculares: '.$faltantes->implode(', ').'.',
            ]);
        }
    }

    private function normalizarCodigoObjetivo(string $codigo): string
    {
        return rtrim(str_replace('О', 'O', trim($codigo)), '.');
    }

    /**
     * @param  EloquentCollection<int, DestrezaCurricular>  $destrezas
     * @param  EloquentCollection<int, IndicadorEvaluacion>  $indicadores
     * @return array{0:?DestrezaCurricular,1:?IndicadorEvaluacion}
     */
    private function resolverReferenciasCurriculares(
        EloquentCollection $destrezas,
        EloquentCollection $indicadores,
        string $codigoIndicador,
    ): array {
        foreach ($destrezas as $destreza) {
            $relacionados = $destreza->indicadores
                ->filter(fn (IndicadorEvaluacion $indicador): bool => $this->codigoBaseIndicador($indicador->codigo) === $codigoIndicador);
            if ($relacionados->isNotEmpty()) {
                return [$destreza, $this->preferirCodigoOriginal($relacionados, $codigoIndicador)];
            }
        }

        $destreza = $destrezas->first();
        if ($destreza) {
            return [$destreza, null];
        }

        $coincidencias = $indicadores
            ->filter(fn (IndicadorEvaluacion $indicador): bool => $this->codigoBaseIndicador($indicador->codigo) === $codigoIndicador);

        return [null, $this->preferirCodigoOriginal($coincidencias, $codigoIndicador)];
    }

    /** @param Collection<int, IndicadorEvaluacion> $indicadores */
    private function preferirCodigoOriginal(Collection $indicadores, string $codigoFuente): ?IndicadorEvaluacion
    {
        return $indicadores->firstWhere('codigo', $codigoFuente) ?? $indicadores->first();
    }

    private function codigoBaseIndicador(string $codigo): string
    {
        return preg_replace('/^Ref\./u', '', $codigo);
    }
}
