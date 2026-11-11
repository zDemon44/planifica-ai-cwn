<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class LectorPrimeraHojaExcel
{
    /** @return array<int, array<string, string>> */
    public function leer(string $ruta): array
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
