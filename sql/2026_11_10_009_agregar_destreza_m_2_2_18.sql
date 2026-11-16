START TRANSACTION;

SET @materia_id := (SELECT id FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1);
SET @curriculo_id := (SELECT id FROM curriculos WHERE codigo = 'obligatorio-elemental' LIMIT 1);

INSERT INTO destrezas_curriculares
    (materia_id, curriculo_id, grado, codigo, descripcion, grupo, activo, created_at, updated_at)
VALUES
(@materia_id, @curriculo_id, 2, 'M.2.2.18', 'Leer horas en punto y medias horas en un reloj analógico, relacionándolas con actividades cotidianas. (Ref. M.2.2.18.)', 4, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.2.18', 'Leer horas y minutos en intervalos de cinco minutos en un reloj analógico, y relacionarlos con situaciones cotidianas. (Ref. M.2.2.18.)', 4, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.2.18', 'M.2.2.18. Leer horas y minutos en un reloj analógico.', 4, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion), grupo = 4, activo = 1, updated_at = NOW();

DELETE relacion
FROM destreza_indicador relacion
JOIN destrezas_curriculares destreza ON destreza.id = relacion.destreza_curricular_id
WHERE destreza.materia_id = @materia_id
  AND destreza.curriculo_id = @curriculo_id
  AND destreza.codigo = 'M.2.2.18'
  AND destreza.grado IN (2, 3, 4);

INSERT INTO destreza_indicador (destreza_curricular_id, indicador_evaluacion_id)
SELECT destreza.id, indicador.id
FROM destrezas_curriculares destreza
JOIN indicadores_evaluacion indicador
  ON indicador.materia_id = destreza.materia_id
 AND indicador.grado = destreza.grado
 AND indicador.grupo = 4
 AND indicador.activo = 1
WHERE destreza.materia_id = @materia_id
  AND destreza.curriculo_id = @curriculo_id
  AND destreza.codigo = 'M.2.2.18'
  AND destreza.grado IN (2, 3, 4);

SET @destreza_cuarto_id := (
    SELECT id FROM destrezas_curriculares
    WHERE materia_id = @materia_id
      AND curriculo_id = @curriculo_id
      AND grado = 4
      AND codigo = 'M.2.2.18'
    LIMIT 1
);
SET @indicador_cuarto_id := (
    SELECT indicador.id
    FROM indicadores_evaluacion indicador
    JOIN destreza_indicador relacion ON relacion.indicador_evaluacion_id = indicador.id
    WHERE relacion.destreza_curricular_id = @destreza_cuarto_id
      AND indicador.codigo = 'I.M.2.4.3'
      AND indicador.activo = 1
    LIMIT 1
);

UPDATE temas_libro tema
JOIN unidades_libro unidad ON unidad.id = tema.unidad_libro_id
JOIN libros_texto libro ON libro.id = unidad.libro_texto_id
SET tema.destreza_curricular_id = @destreza_cuarto_id,
    tema.indicador_evaluacion_id = @indicador_cuarto_id,
    tema.updated_at = NOW()
WHERE libro.codigo = 'matematica-4to'
  AND tema.codigo_destreza_fuente = 'M.2.2.18'
  AND tema.codigo_indicador_fuente = 'I.M.2.4.3';

COMMIT;
