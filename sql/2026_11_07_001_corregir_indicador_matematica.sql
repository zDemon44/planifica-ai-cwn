START TRANSACTION;

UPDATE indicadores_evaluacion
SET descripcion = 'I.M.2.2.2. Aplica de manera razonada la composición y descomposición de unidades, decenas, centenas y unidades de mil, para establecer relaciones de orden (=, <, >), calcula adiciones y sustracciones, y da solución a problemas matemáticos sencillos del entorno. (I.2., S.4.)',
    updated_at = CURRENT_TIMESTAMP
WHERE codigo = 'I.M.2.2.2'
  AND grado = 4;

COMMIT;

SELECT grado, codigo, descripcion
FROM indicadores_evaluacion
WHERE codigo = 'I.M.2.2.2'
ORDER BY grado;
