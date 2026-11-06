START TRANSACTION;

CREATE TABLE objetivos_curriculares (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    materia_id BIGINT UNSIGNED NOT NULL,
    subnivel VARCHAR(40) NOT NULL,
    codigo VARCHAR(60) NOT NULL,
    descripcion LONGTEXT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT objetivos_curriculares_materia_id_foreign FOREIGN KEY (materia_id) REFERENCES materias(id),
    CONSTRAINT objetivo_materia_subnivel_codigo_unique UNIQUE (materia_id, subnivel, codigo)
);

CREATE TABLE objetivo_planificacion (
    objetivo_curricular_id BIGINT UNSIGNED NOT NULL,
    planificacion_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT objetivo_planificacion_primary PRIMARY KEY (objetivo_curricular_id, planificacion_id),
    CONSTRAINT objetivo_planificacion_objetivo_foreign FOREIGN KEY (objetivo_curricular_id) REFERENCES objetivos_curriculares(id),
    CONSTRAINT objetivo_planificacion_planificacion_foreign FOREIGN KEY (planificacion_id) REFERENCES planificaciones(id) ON DELETE CASCADE
);

INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.1.', 'Explicar y construir patrones de figuras y numéricos relacionándolos con la suma, la resta y la multiplicación, para desarrollar el pensamiento lógico matemático.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;
INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.2.', 'Utilizar objetos del entorno para formar conjuntos, establecer gráficamente la correspondencia entre sus elementos y desarrollar la comprensión de modelos matemáticos.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;
INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.3.', 'Integrar concretamente el concepto de número, y reconocer situaciones del entorno en las que se presenten problemas que requieran la formulación de expresiones matemáticas sencillas, para resolverlas, de forma individual o grupal, utilizando los algoritmos de adición, sustracción, multiplicación y división exacta.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;
INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.4.', 'Aplicar estrategias de conteo, procedimientos de cálculos de suma, resta, multiplicación y divisiones del 0 al 9 999, para resolver de forma colaborativa problemas cotidianos de su entorno.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;
INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.5.', 'Comprender el espacio que lo rodea, valorar lugares históricos, turísticos y bienes naturales, identificando como conceptos matemáticos los elementos y propiedades de cuerpos y figuras geométricas en objetos del entorno.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;
INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.6.', 'Resolver situaciones cotidianas que impliquen la medición, estimación y el cálculo de longitudes, capacidades y masas, con unidades convencionales y no convencionales de objetos de su entorno, para una mejor comprensión del espacio que le rodea, la valoración de su tiempo y el de los otros, y el fomento de la honestidad e integridad en sus actos.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;
INSERT INTO objetivos_curriculares (materia_id, subnivel, codigo, descripcion, activo, created_at, updated_at)
SELECT id, 'basica_elemental', 'O.M.2.7.', 'Participar en proyectos de análisis de información del entorno inmediato, mediante la recolección y representación de datos estadísticos en pictogramas y diagramas de barras; potenciando, así, el pensamiento lógico-matemático y creativo, al interpretar la información y expresar conclusiones asumiendo compromisos.', 1, NOW(), NOW() FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1;

COMMIT;
