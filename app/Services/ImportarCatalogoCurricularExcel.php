<?php

namespace App\Services;

use App\Models\CompetenciaCurricular;
use App\Models\DestrezaCurricular;
use App\Models\IndicadorEvaluacion;
use App\Models\Materia;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ImportarCatalogoCurricularExcel
{
    /** @return array{destrezas:int, indicadores:int, relaciones:int, competencias:int} */
    public function handle(Materia $materia, UploadedFile|string $archivo): array
    {
        $ruta = $archivo instanceof UploadedFile ? $archivo->getRealPath() : $archivo;
        $filas = $this->leerPrimeraHoja((string) $ruta);
        $grupos = $this->interpretar($filas);

        return DB::transaction(function () use ($materia, $grupos): array {
            $idsDestrezas = [];
            $idsIndicadores = [];
            $relaciones = [];

            foreach ($grupos as $grupo => $datos) {
                foreach ([2, 3, 4] as $grado) {
                    $destrezas = [];
                    foreach ($datos['destrezas'][$grado] as $registro) {
                        $destreza = DestrezaCurricular::updateOrCreate(
                            ['materia_id' => $materia->id, 'grado' => $grado, 'codigo' => $registro['codigo']],
                            ['descripcion' => $registro['descripcion'], 'grupo' => $grupo, 'activo' => true]
                        );
                        $this->sincronizarCompetencias($destreza, $registro['competencias']);
                        $idsDestrezas[] = $destreza->id;
                        $destrezas[] = $destreza;
                    }

                    $indicadores = [];
                    foreach ($datos['indicadores'][$grado] as $registro) {
                        $indicador = IndicadorEvaluacion::updateOrCreate(
                            ['materia_id' => $materia->id, 'grado' => $grado, 'codigo' => $registro['codigo']],
                            ['descripcion' => $registro['descripcion'], 'grupo' => $grupo, 'activo' => true]
                        );
                        $this->sincronizarCompetencias($indicador, $registro['competencias']);
                        $idsIndicadores[] = $indicador->id;
                        $indicadores[] = $indicador;
                    }

                    foreach ($destrezas as $destreza) {
                        foreach ($indicadores as $indicador) {
                            $relaciones[$destreza->id][$indicador->id] = $indicador->id;
                        }
                    }
                }
            }

            DestrezaCurricular::where('materia_id', $materia->id)->whereIn('grado', [2, 3, 4])
                ->when($idsDestrezas, fn ($consulta) => $consulta->whereNotIn('id', array_unique($idsDestrezas)))
                ->update(['activo' => false]);
            IndicadorEvaluacion::where('materia_id', $materia->id)->whereIn('grado', [2, 3, 4])
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
            }
            if (! $grupoActual) {
                continue;
            }
            $grupos[$grupoActual] ??= ['destrezas' => [2 => [], 3 => [], 4 => []], 'indicadores' => [2 => [], 3 => [], 4 => []]];

            foreach ([2 => 'D', 3 => 'E', 4 => 'F'] as $grado => $columna) {
                if ($registro = $this->registro($fila[$columna] ?? '', $fila['C'] ?? '', 'destreza', $numeroFila)) {
                    $grupos[$grupoActual]['destrezas'][$grado][$registro['codigo']] = $registro;
                }
            }
            foreach ([2 => 'I', 3 => 'J', 4 => 'K'] as $grado => $columna) {
                if ($registro = $this->registro($fila[$columna] ?? '', $fila['H'] ?? '', 'indicador', $numeroFila)) {
                    $grupos[$grupoActual]['indicadores'][$grado][$registro['codigo']] = $registro;
                }
            }
        }

        if ($grupos === []) {
            throw ValidationException::withMessages(['archivo' => 'No se encontraron grupos curriculares desde la fila 6.']);
        }
        foreach ($grupos as $numero => $grupo) {
            foreach ([2, 3, 4] as $grado) {
                if ($grupo['destrezas'][$grado] === [] || $grupo['indicadores'][$grado] === []) {
                    throw ValidationException::withMessages(['archivo' => "El grupo {$numero} no tiene destrezas e indicadores completos para {$grado}.º grado."]);
                }
            }
        }

        return $grupos;
    }

    /** @return array{codigo:string, descripcion:string, competencias:array<int,string>}|null */
    private function registro(string $descripcion, string $competencia, string $tipo, int $fila): ?array
    {
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

        return ['codigo' => $coincidencia[1], 'descripcion' => $descripcion, 'competencias' => $competencias];
    }

    /** @param array<int, string> $codigos */
    private function sincronizarCompetencias(DestrezaCurricular|IndicadorEvaluacion $registro, array $codigos): void
    {
        $ids = collect($codigos)->map(fn (string $codigo): int => CompetenciaCurricular::firstOrCreate(['codigo' => $codigo])->id);
        $registro->competencias()->sync($ids->all());
    }

    /** @return array<int, array<string, string>> */
    private function leerPrimeraHoja(string $ruta): array
    {
        $zip = new ZipArchive;
        if ($zip->open($ruta) !== true) {
            throw ValidationException::withMessages(['archivo' => 'No se pudo abrir el archivo Excel.']);
        }
        try {
            $compartidos = $this->leerTextosCompartidos($zip);
            $xml = $zip->getFromName($this->rutaPrimeraHoja($zip));
            if ($xml === false) {
                throw ValidationException::withMessages(['archivo' => 'El Excel no contiene una primera hoja válida.']);
            }
            $documento = new DOMDocument;
            $documento->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
            $xpath = new DOMXPath($documento);
            $filas = [];
            foreach ($xpath->query('//*[local-name()="row"]') as $nodoFila) {
                $numero = (int) $nodoFila->getAttribute('r');
                foreach ($xpath->query('./*[local-name()="c"]', $nodoFila) as $celda) {
                    preg_match('/^[A-Z]+/', $celda->getAttribute('r'), $columna);
                    $tipo = $celda->getAttribute('t');
                    $valor = '';
                    if ($tipo === 'inlineStr') {
                        foreach ($xpath->query('.//*[local-name()="t"]', $celda) as $texto) {
                            $valor .= $texto->textContent;
                        }
                    } else {
                        $nodoValor = $xpath->query('./*[local-name()="v"]', $celda)->item(0);
                        $valor = $nodoValor?->textContent ?? '';
                        if ($tipo === 's' && isset($compartidos[(int) $valor])) {
                            $valor = $compartidos[(int) $valor];
                        }
                    }
                    $filas[$numero][$columna[0]] = $valor;
                }
            }

            return $filas;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function leerTextosCompartidos(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $documento = new DOMDocument;
        $documento->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        $xpath = new DOMXPath($documento);
        $textos = [];
        foreach ($xpath->query('//*[local-name()="si"]') as $elemento) {
            $texto = '';
            foreach ($xpath->query('.//*[local-name()="t"]', $elemento) as $fragmento) {
                $texto .= $fragmento->textContent;
            }
            $textos[] = $texto;
        }

        return $textos;
    }

    private function rutaPrimeraHoja(ZipArchive $zip): string
    {
        $libroXml = $zip->getFromName('xl/workbook.xml');
        $relacionesXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($libroXml === false || $relacionesXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $libro = new DOMDocument;
        $libro->loadXML($libroXml, LIBXML_NONET | LIBXML_NOBLANKS);
        $xpathLibro = new DOMXPath($libro);
        $hoja = $xpathLibro->query('//*[local-name()="sheet"]')->item(0);
        $relacionId = $hoja?->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
        if (! $relacionId) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relaciones = new DOMDocument;
        $relaciones->loadXML($relacionesXml, LIBXML_NONET | LIBXML_NOBLANKS);
        $xpathRelaciones = new DOMXPath($relaciones);
        foreach ($xpathRelaciones->query('//*[local-name()="Relationship"]') as $relacion) {
            if ($relacion->getAttribute('Id') === $relacionId) {
                $objetivo = str_replace('\\', '/', $relacion->getAttribute('Target'));

                return str_starts_with($objetivo, '/') ? ltrim($objetivo, '/') : 'xl/'.ltrim($objetivo, '/');
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }
}
