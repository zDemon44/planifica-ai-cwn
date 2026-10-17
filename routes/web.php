<?php

$archivosDeRutas = glob(__DIR__.'/web/*.php');

sort($archivosDeRutas);

foreach ($archivosDeRutas as $archivoDeRutas) {
    require $archivoDeRutas;
}
