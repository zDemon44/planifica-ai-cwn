START TRANSACTION;

ALTER TABLE periodos_planificacion
    ADD COLUMN dua_experiencia LONGTEXT NULL AFTER experiencia,
    ADD COLUMN dua_reflexion LONGTEXT NULL AFTER reflexion,
    ADD COLUMN dua_conceptualizacion LONGTEXT NULL AFTER conceptualizacion,
    ADD COLUMN dua_aplicacion LONGTEXT NULL AFTER aplicacion;

COMMIT;
