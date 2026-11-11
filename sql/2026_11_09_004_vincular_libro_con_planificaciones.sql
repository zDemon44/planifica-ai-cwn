ALTER TABLE planificaciones
    ADD unidad_libro_id BIGINT UNSIGNED NULL AFTER asignacion_id,
    ADD CONSTRAINT planificaciones_unidad_libro_id_foreign
        FOREIGN KEY (unidad_libro_id) REFERENCES unidades_libro(id);

ALTER TABLE periodos_planificacion
    ADD tema_libro_id BIGINT UNSIGNED NULL AFTER planificacion_id,
    ADD CONSTRAINT periodos_planificacion_tema_libro_id_foreign
        FOREIGN KEY (tema_libro_id) REFERENCES temas_libro(id);
