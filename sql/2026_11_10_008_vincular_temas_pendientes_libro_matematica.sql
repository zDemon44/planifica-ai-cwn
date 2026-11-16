START TRANSACTION;

UPDATE temas_libro tema
JOIN unidades_libro unidad ON unidad.id = tema.unidad_libro_id
JOIN libros_texto libro ON libro.id = unidad.libro_texto_id
JOIN destrezas_curriculares destreza
  ON destreza.materia_id = libro.materia_id
 AND destreza.grado = libro.grado
 AND destreza.codigo = tema.codigo_destreza_fuente
 AND destreza.activo = 1
JOIN destreza_indicador relacion
  ON relacion.destreza_curricular_id = destreza.id
JOIN indicadores_evaluacion indicador
  ON indicador.id = relacion.indicador_evaluacion_id
 AND indicador.materia_id = libro.materia_id
 AND indicador.grado = libro.grado
 AND indicador.grupo = destreza.grupo
 AND indicador.activo = 1
 AND REPLACE(indicador.codigo, 'Ref.', '') = tema.codigo_indicador_fuente
SET tema.destreza_curricular_id = destreza.id,
    tema.indicador_evaluacion_id = indicador.id,
    tema.updated_at = NOW()
WHERE libro.codigo = 'matematica-4to'
  AND tema.activo = 1
  AND (tema.destreza_curricular_id IS NULL OR tema.indicador_evaluacion_id IS NULL);

COMMIT;

SELECT unidad.numero AS unidad,
       tema.orden,
       tema.etiqueta,
       tema.codigo_destreza_fuente,
       tema.codigo_indicador_fuente
FROM temas_libro tema
JOIN unidades_libro unidad ON unidad.id = tema.unidad_libro_id
JOIN libros_texto libro ON libro.id = unidad.libro_texto_id
WHERE libro.codigo = 'matematica-4to'
  AND tema.activo = 1
  AND (tema.destreza_curricular_id IS NULL OR tema.indicador_evaluacion_id IS NULL)
ORDER BY unidad.numero, tema.orden;
