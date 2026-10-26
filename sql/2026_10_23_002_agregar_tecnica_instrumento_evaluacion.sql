START TRANSACTION;

ALTER TABLE periodos_planificacion
    ADD COLUMN tecnica_evaluacion LONGTEXT NULL AFTER orientaciones_evaluacion,
    ADD COLUMN instrumento_evaluacion LONGTEXT NULL AFTER tecnica_evaluacion;

UPDATE periodos_planificacion
SET tecnica_evaluacion = orientaciones_evaluacion
WHERE orientaciones_evaluacion IS NOT NULL;

COMMIT;
