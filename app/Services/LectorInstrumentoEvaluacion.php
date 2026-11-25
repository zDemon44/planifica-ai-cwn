<?php

namespace App\Services;

/**
 * Convierte el texto del instrumento de evaluación en un cuadro para marcar:
 * título, niveles de la escala y criterios numerados con su puntaje.
 */
class LectorInstrumentoEvaluacion
{
    private const MINIMO_CRITERIOS = 2;

    private const MAXIMO_NIVELES = 5;

    /**
     * @return array{titulo: ?string, escala: list<string>, criterios: list<array{texto: string, puntos: ?float}>, total: ?float}|null
     */
    public function leer(?string $texto): ?array
    {
        $titulo = null;
        $escala = [];
        $criterios = [];

        foreach (preg_split('/\R/u', trim((string) $texto)) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '' || preg_match('/^(criterios|preguntas|ítems|items|ejercicios)\s*:?$/iu', $linea)) {
                continue;
            }

            if (preg_match('/^escala\s*:\s*(.+)$/iu', $linea, $coincidencia)) {
                $escala = $this->niveles($coincidencia[1]);

                continue;
            }

            if (preg_match('/^total\b/iu', $linea)) {
                continue;
            }

            if (preg_match('/^(\d{1,2})\s*[.)\-]\s*(.+)$/u', $linea, $coincidencia)) {
                $criterios[] = $this->criterio($coincidencia[2]);

                continue;
            }

            if ($criterios === [] && $titulo === null) {
                $titulo = rtrim($linea, '.');
            } elseif ($criterios !== []) {
                $ultimo = array_pop($criterios);
                $criterios[] = $this->criterio($ultimo['texto'].' '.$linea, $ultimo['puntos']);
            }
        }

        if (count($criterios) < self::MINIMO_CRITERIOS) {
            return null;
        }

        $puntajes = array_filter(array_column($criterios, 'puntos'), fn (?float $puntos): bool => $puntos !== null);

        return [
            'titulo' => $titulo,
            'escala' => $escala,
            'criterios' => $criterios,
            'total' => $puntajes === [] ? null : round(array_sum($puntajes), 2),
        ];
    }

    /**
     * @return list<string>
     */
    private function niveles(string $escala): array
    {
        $niveles = preg_split('/\s*(?:\/|·|\||;|,)\s*/u', rtrim(trim($escala), '.')) ?: [];

        return array_slice(array_values(array_filter(array_map('trim', $niveles), 'strlen')), 0, self::MAXIMO_NIVELES);
    }

    /**
     * @return array{texto: string, puntos: ?float}
     */
    private function criterio(string $texto, ?float $puntosPrevios = null): array
    {
        $patronPuntos = '/\s*(?:\(\s*(\d+(?:[.,]\d+)?)\s*(?:pts?\.?|puntos?)\s*\)|[-–—:]\s*(\d+(?:[.,]\d+)?)\s*(?:pts?\.?|puntos?))\s*\.?$/iu';
        if (preg_match($patronPuntos, $texto, $coincidencia)) {
            return [
                'texto' => rtrim(trim(preg_replace($patronPuntos, '', $texto) ?? $texto), ' .'),
                'puntos' => (float) str_replace(',', '.', $coincidencia[1] !== '' ? $coincidencia[1] : $coincidencia[2]),
            ];
        }

        return ['texto' => rtrim(trim($texto), ' .'), 'puntos' => $puntosPrevios];
    }
}
