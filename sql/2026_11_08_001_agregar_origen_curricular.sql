START TRANSACTION;

CREATE TABLE curriculos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(80) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    vigente TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT curriculos_codigo_unique UNIQUE (codigo)
);

INSERT INTO curriculos (id, codigo, nombre, vigente, created_at, updated_at) VALUES
(1, 'priorizado-2025', 'Currículo priorizado 2025', 1, NOW(), NOW()),
(2, 'obligatorio-elemental', 'Currículo de los Niveles de Educación Obligatoria - Subnivel Elemental', 0, NOW(), NOW());

ALTER TABLE destrezas_curriculares
    ADD INDEX destrezas_curriculares_materia_id_index (materia_id);

ALTER TABLE destrezas_curriculares
    DROP INDEX destreza_materia_grado_codigo_unique,
    ADD COLUMN curriculo_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER materia_id,
    ADD CONSTRAINT destrezas_curriculares_curriculo_id_foreign FOREIGN KEY (curriculo_id) REFERENCES curriculos(id),
    ADD CONSTRAINT destreza_curriculo_materia_grado_codigo_unique UNIQUE (curriculo_id, materia_id, grado, codigo);

ALTER TABLE indicadores_evaluacion
    ADD INDEX indicadores_evaluacion_materia_id_index (materia_id);

ALTER TABLE indicadores_evaluacion
    DROP INDEX indicador_materia_grado_codigo_unique,
    ADD COLUMN curriculo_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER materia_id,
    ADD CONSTRAINT indicadores_evaluacion_curriculo_id_foreign FOREIGN KEY (curriculo_id) REFERENCES curriculos(id),
    ADD CONSTRAINT indicador_curriculo_materia_grado_codigo_unique UNIQUE (curriculo_id, materia_id, grado, codigo);

COMMIT;
