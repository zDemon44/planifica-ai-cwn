<?php

namespace Tests\Unit;

use App\Services\LectorInstrumentoEvaluacion;
use PHPUnit\Framework\TestCase;

class LectorInstrumentoEvaluacionTest extends TestCase
{
    public function test_lee_titulo_escala_criterios_y_puntajes(): void
    {
        $instrumento = (new LectorInstrumentoEvaluacion)->leer(<<<'TEXTO'
Lista de cotejo: Multiplicación con modelos lineal y grupal
Escala: Logrado / En proceso / Iniciado
Criterios:
1. Representa 4 grupos de 3 tapas como 4 x 3 = 12 (3 puntos)
2. Ubica 3 saltos de 5 en la recta numérica (2,5 puntos)
3. Resuelve el problema de las 5 macetas con 4 tomates (4,5 puntos).
Total: 10 puntos
TEXTO);

        $this->assertSame('Lista de cotejo: Multiplicación con modelos lineal y grupal', $instrumento['titulo']);
        $this->assertSame(['Logrado', 'En proceso', 'Iniciado'], $instrumento['escala']);
        $this->assertSame([
            ['texto' => 'Representa 4 grupos de 3 tapas como 4 x 3 = 12', 'puntos' => 3.0],
            ['texto' => 'Ubica 3 saltos de 5 en la recta numérica', 'puntos' => 2.5],
            ['texto' => 'Resuelve el problema de las 5 macetas con 4 tomates', 'puntos' => 4.5],
        ], $instrumento['criterios']);
        $this->assertSame(10.0, $instrumento['total']);
    }

    public function test_no_confunde_puntos_del_contenido_con_el_puntaje(): void
    {
        $instrumento = (new LectorInstrumentoEvaluacion)->leer("Escala: Sí / No\n1. Ubica 3 puntos en el plano cartesiano\n2. Une los puntos con una regla");

        $this->assertSame('Ubica 3 puntos en el plano cartesiano', $instrumento['criterios'][0]['texto']);
        $this->assertNull($instrumento['criterios'][0]['puntos']);
        $this->assertNull($instrumento['total']);
    }

    public function test_devuelve_null_cuando_el_texto_no_tiene_criterios_numerados(): void
    {
        $lector = new LectorInstrumentoEvaluacion;

        $this->assertNull($lector->leer('Lista de cotejo con criterios de logro.'));
        $this->assertNull($lector->leer(null));
    }
}
