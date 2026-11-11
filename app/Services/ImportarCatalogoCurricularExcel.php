<?php

namespace App\Services;

use App\Models\CompetenciaCurricular;
use App\Models\Curriculo;
use App\Models\DestrezaCurricular;
use App\Models\IndicadorEvaluacion;
use App\Models\Materia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportarCatalogoCurricularExcel
{
    public function __construct(private LectorPrimeraHojaExcel $lectorExcel) {}

    /** @return array{destrezas:int, indicadores:int, relaciones:int, competencias:int} */
    public function handle(Materia $materia, Curriculo $curriculo, UploadedFile|string $archivo): array
    {
        $ruta = $archivo instanceof UploadedFile ? $archivo->getRealPath() : $archivo;
        $filas = $this->lectorExcel->leer((string) $ruta);
        $grupos = $this->interpretar($filas);

        return DB::transaction(function () use ($materia, $curriculo, $grupos): array {
            $idsDestrezas = [];
            $idsIndicadores = [];
            $relaciones = [];

            foreach ($grupos as $grupo => $datos) {
                foreach ([2, 3, 4] as $grado) {
                    $destrezas = [];
                    foreach ($datos['destrezas'][$grado] as $registro) {
                        $destreza = DestrezaCurricular::updateOrCreate(
                            [
                                'materia_id' => $materia->id,
                                'curriculo_id' => $curriculo->id,
                                'grado' => $grado,
                                'codigo' => $registro['codigo'],
                            ],
                            ['descripcion' => $registro['descripcion'], 'grupo' => $grupo, 'activo' => true]
                        );
                        $this->sincronizarCompetencias($destreza, $registro['competencias']);
                        $idsDestrezas[] = $destreza->id;
                        $destrezas[] = $destreza;
                        $relaciones[$destreza->id] ??= [];
                    }

                    $indicadores = [];
                    foreach ($datos['indicadores'][$grado] as $registro) {
                        $indicador = IndicadorEvaluacion::updateOrCreate(
                            [
                                'materia_id' => $materia->id,
                                'curriculo_id' => $curriculo->id,
                                'grado' => $grado,
                                'codigo' => $registro['codigo'],
                            ],
                            ['descripcion' => $registro['descripcion'], 'grupo' => $grupo, 'activo' => true]
                        );
                        $this->sincronizarCompetencias($indicador, $registro['competencias']);
                        $idsIndicadores[] = $indicador->id;
                        $indicadores[] = $indicador;
                    }

                    if ($destrezas !== []) {
                        $indicadoresVigentes = IndicadorEvaluacion::query()
                            ->where('materia_id', $materia->id)
                            ->where('curriculo_id', '<>', $curriculo->id)
                            ->where('grado', $grado)
                            ->where('grupo', $grupo)
                            ->where('activo', true)
                            ->whereHas('curriculo', fn ($consulta) => $consulta->where('vigente', true))
                            ->get()
                            ->all();
                        $indicadores = array_merge($indicadores, $indicadoresVigentes);
                    }

                    foreach ($destrezas as $destreza) {
                        foreach ($indicadores as $indicador) {
                            $relaciones[$destreza->id][$indicador->id] = $indicador->id;
                        }
                    }
                }
            }

            DestrezaCurricular::where('materia_id', $materia->id)->where('curriculo_id', $curriculo->id)
                ->whereIn('grado', [2, 3, 4])
                ->when($idsDestrezas, fn ($consulta) => $consulta->whereNotIn('id', array_unique($idsDestrezas)))
                ->update(['activo' => false]);
            IndicadorEvaluacion::where('materia_id', $materia->id)->where('curriculo_id', $curriculo->id)
                ->whereIn('grado', [2, 3, 4])
                ->when($idsIndicadores, fn ($consulta) => $consulta->whereNotIn('id', array_unique($idsIndicadores)))
                ->update(['activo' => false]);

            foreach ($relaciones as $destrezaId => $indicadorIds) {
                DestrezaCurricular::findOrFail($destrezaId)->indicadores()->sync(array_values($indicadorIds));
            }

            return [
                'destrezas' => count(array_unique($idsDestrezas)),
                'indicadores' => count(array_unique($idsIndicadores)),
                'relaciones' => array_sum(array_map('count', $relaciones)),
                'competencias' => CompetenciaCurricular::whereHas('destrezas', fn ($consulta) => $consulta->where('materia_id', $materia->id))
                    ->orWhereHas('indicadores', fn ($consulta) => $consulta->where('materia_id', $materia->id))->count(),
            ];
        }, 3);
    }

    /** @param array<int, array<string, string>> $filas */
    private function interpretar(array $filas): array
    {
        $grupos = [];
        $grupoActual = null;
        foreach ($filas as $numeroFila => $fila) {
            if ($numeroFila < 6) {
                continue;
            }
            if (($fila['A'] ?? '') !== '') {
                $grupoActual = (int) $fila['A'];
                if ($grupoActual === 0) {
                    $grupoActual = 1;
                }
            }
            if ($grupoActual === null) {
                continue;
            }
            $grupos[$grupoActual] ??= ['destrezas' => [2 => [], 3 => [], 4 => []], 'indicadores' => [2 => [], 3 => [], 4 => []]];

            foreach ([2 => 'D', 3 => 'E', 4 => 'F'] as $grado => $columna) {
                if ($registro = $this->registro($fila[$columna] ?? '', $fila['C'] ?? '', 'destreza', $numeroFila)) {
                    $grupos[$grupoActual]['destrezas'][$grado][$registro['codigo']] = $registro;
                }
            }
            foreach ([2 => 'I', 3 => 'J', 4 => 'K'] as $grado => $columna) {
                if ($registro = $this->registro(
                    $fila[$columna] ?? '',
                    $fila['H'] ?? '',
                    'indicador',
                    $numeroFila,
                    $this->indicadorEsReferencia($fila['G'] ?? '')
                )) {
                    $grupos[$grupoActual]['indicadores'][$grado][$registro['codigo']] = $registro;
                }
            }
        }

        if ($grupos === []) {
            throw ValidationException::withMessages(['archivo' => 'No se encontraron grupos curriculares desde la fila 6.']);
        }
        foreach ($grupos as $numero => $grupo) {
            foreach ([2, 3, 4] as $grado) {
                if ($grupo['destrezas'][$grado] === [] && $grupo['indicadores'][$grado] === []) {
                    throw ValidationException::withMessages(['archivo' => "El grupo {$numero} no tiene información curricular para {$grado}.º grado."]);
                }
            }
        }

        return $grupos;
    }

    /** @return array{codigo:string, descripcion:string, competencias:array<int,string>}|null */
    private function registro(
        string $descripcion,
        string $competencia,
        string $tipo,
        int $fila,
        bool $esReferencia = false,
    ): ?array {
        $descripcion = trim((string) preg_replace('/\s+/u', ' ', $descripcion));
        if ($descripcion === '') {
            return null;
        }
        $patron = $tipo === 'destreza'
            ? '/(?<!I\.)\b([A-ZÁÉÍÓÚÑ]{1,5}\.\d+\.\d+\.\d+)\b/u'
            : '/\b(I\.[A-ZÁÉÍÓÚÑ]{1,5}\.\d+\.\d+\.\d+)\b/u';
        if (! preg_match($patron, $descripcion, $coincidencia)) {
            throw ValidationException::withMessages(['archivo' => "No se pudo identificar el código de {$tipo} en la fila {$fila}."]);
        }

        $competencias = array_values(array_unique(array_filter(array_map(
            fn (string $codigo): string => mb_strtoupper(trim($codigo)),
            preg_split('/\s*,\s*/u', trim($competencia)) ?: []
        ))));

        $codigo = $coincidencia[1];
        if ($tipo === 'indicador' && $esReferencia) {
            $codigo = 'Ref.'.$codigo;
        }

        return ['codigo' => $codigo, 'descripcion' => $descripcion, 'competencias' => $competencias];
    }

    private function indicadorEsReferencia(string $descripcionOriginal): bool
    {
        return preg_match('/\(\s*Ref\.?\s*I\./iu', $descripcionOriginal) === 1;
    }

    /** @param array<int, string> $codigos */
    private function sincronizarCompetencias(DestrezaCurricular|IndicadorEvaluacion $registro, array $codigos): void
    {
        $ids = collect($codigos)->map(fn (string $codigo): int => CompetenciaCurricular::firstOrCreate(['codigo' => $codigo])->id);
        $registro->competencias()->sync($ids->all());
    }
}
