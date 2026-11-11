CREATE TABLE libros_texto (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    materia_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(80) NOT NULL,
    grado TINYINT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    editorial VARCHAR(255) NULL,
    edicion VARCHAR(255) NULL,
    anio_publicacion SMALLINT UNSIGNED NULL,
    archivo_origen VARCHAR(500) NULL,
    archivo_pdf VARCHAR(500) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT libros_texto_codigo_unique UNIQUE (codigo),
    CONSTRAINT libros_texto_materia_id_foreign FOREIGN KEY (materia_id) REFERENCES materias(id)
);

CREATE TABLE unidades_libro (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libro_texto_id BIGINT UNSIGNED NOT NULL,
    numero SMALLINT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT unidad_libro_numero_unique UNIQUE (libro_texto_id, numero),
    CONSTRAINT unidades_libro_libro_texto_id_foreign FOREIGN KEY (libro_texto_id) REFERENCES libros_texto(id) ON DELETE CASCADE
);

CREATE TABLE objetivo_unidad_libro (
    objetivo_curricular_id BIGINT UNSIGNED NOT NULL,
    unidad_libro_id BIGINT UNSIGNED NOT NULL,
    CONSTRAINT objetivo_unidad_libro_primary PRIMARY KEY (objetivo_curricular_id, unidad_libro_id),
    CONSTRAINT objetivo_unidad_libro_objetivo_curricular_id_foreign FOREIGN KEY (objetivo_curricular_id) REFERENCES objetivos_curriculares(id),
    CONSTRAINT objetivo_unidad_libro_unidad_libro_id_foreign FOREIGN KEY (unidad_libro_id) REFERENCES unidades_libro(id) ON DELETE CASCADE
);

CREATE TABLE temas_libro (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unidad_libro_id BIGINT UNSIGNED NOT NULL,
    destreza_curricular_id BIGINT UNSIGNED NULL,
    indicador_evaluacion_id BIGINT UNSIGNED NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    etiqueta VARCHAR(40) NOT NULL,
    titulo VARCHAR(500) NOT NULL,
    codigo_destreza_fuente VARCHAR(60) NOT NULL,
    codigo_indicador_fuente VARCHAR(60) NOT NULL,
    pagina_inicio SMALLINT UNSIGNED NULL,
    pagina_fin SMALLINT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT tema_libro_orden_unique UNIQUE (unidad_libro_id, orden),
    CONSTRAINT temas_libro_unidad_libro_id_foreign FOREIGN KEY (unidad_libro_id) REFERENCES unidades_libro(id) ON DELETE CASCADE,
    CONSTRAINT temas_libro_destreza_curricular_id_foreign FOREIGN KEY (destreza_curricular_id) REFERENCES destrezas_curriculares(id),
    CONSTRAINT temas_libro_indicador_evaluacion_id_foreign FOREIGN KEY (indicador_evaluacion_id) REFERENCES indicadores_evaluacion(id)
);
