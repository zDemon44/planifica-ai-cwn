START TRANSACTION;

UPDATE destrezas_curriculares d
JOIN materias m ON m.id = d.materia_id
SET d.grupo = 5
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND d.grupo = 4;

UPDATE destrezas_curriculares d
JOIN materias m ON m.id = d.materia_id
SET d.grupo = 4
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND d.grupo = 3;

UPDATE destrezas_curriculares d
JOIN materias m ON m.id = d.materia_id
SET d.grupo = 3
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND d.grupo = 2;

UPDATE destrezas_curriculares d
JOIN materias m ON m.id = d.materia_id
SET d.grupo = 2
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND d.grupo = 1;

UPDATE indicadores_evaluacion i
JOIN materias m ON m.id = i.materia_id
SET i.grupo = 5
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND i.grupo = 4;

UPDATE indicadores_evaluacion i
JOIN materias m ON m.id = i.materia_id
SET i.grupo = 4
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND i.grupo = 3;

UPDATE indicadores_evaluacion i
JOIN materias m ON m.id = i.materia_id
SET i.grupo = 3
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND i.grupo = 2;

UPDATE indicadores_evaluacion i
JOIN materias m ON m.id = i.materia_id
SET i.grupo = 2
WHERE m.nombre IN ('Matemática', 'Matemáticas') AND i.grupo = 1;

COMMIT;
