START TRANSACTION;

DELETE di
FROM destreza_indicador di
JOIN destrezas_curriculares d ON d.id = di.destreza_curricular_id
JOIN curriculos c ON c.id = d.curriculo_id
WHERE c.codigo = 'obligatorio-elemental'
  AND d.codigo IN ('M.2.1.8', 'M.2.1.10')
  AND d.grado IN (2, 3, 4);

UPDATE destrezas_curriculares d
JOIN curriculos c ON c.id = d.curriculo_id
SET d.grupo = 1
WHERE c.codigo = 'obligatorio-elemental'
  AND d.codigo IN ('M.2.1.8', 'M.2.1.10')
  AND d.grado IN (2, 3, 4);

COMMIT;
