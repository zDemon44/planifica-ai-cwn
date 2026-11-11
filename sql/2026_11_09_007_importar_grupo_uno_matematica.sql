START TRANSACTION;

SET @materia_id := (SELECT id FROM materias WHERE nombre IN ('Matemática', 'Matemáticas') LIMIT 1);
SET @curriculo_id := (SELECT id FROM curriculos WHERE codigo = 'priorizado-2025' LIMIT 1);
SET @curriculo_anterior_id := (SELECT id FROM curriculos WHERE codigo = 'obligatorio-elemental' LIMIT 1);

INSERT INTO destrezas_curriculares
    (materia_id, curriculo_id, grado, codigo, descripcion, grupo, activo, created_at, updated_at)
VALUES
(@materia_id, @curriculo_id, 2, 'M.2.1.1', 'Representar gráficamente conjuntos de objetos del entorno, agrupándolos por una propiedad común e identificando los elementos que pertenecen o no al conjunto. (Ref. M.2.1.1.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.1', 'Representar gráficamente conjuntos y subconjuntos de objetos, discriminando una o más propiedades o atributos. (Ref. M.2.1.1.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.1', 'M.2.1.1. Representar gráficamente conjuntos y subconjuntos, discriminando las propiedades o atributos de los objetos.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.2', 'Describir y reproducir patrones sencillos de objetos y figuras basados en un atributo, como color, forma o tamaño. (Ref. M.2.1.2.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.2', 'Describir y reproducir patrones de objetos y figuras basados en uno o dos atributos. (Ref. M.2.1.2.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.2', 'M.2.1.2. Describir y reproducir patrones de objetos y figuras basándose en sus atributos.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.3', 'Describir y reproducir patrones numéricos de suma y resta con números naturales hasta 99, contando hacia adelante y hacia atrás. (Ref. M.2.1.3.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.3', 'Describir y reproducir patrones numéricos basados en sumas y restas con números naturales hasta 999, contando hacia adelante y hacia atrás. (Ref. M.2.1.3.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.3', 'M.2.1.3. Describir y reproducir patrones numéricos basados en sumas y restas, contando hacia adelante y hacia atrás.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.4', 'Describir y reproducir patrones numéricos crecientes mediante sumas reiteradas con números naturales hasta 99. (Ref. M.2.1.4.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.4', 'Describir y reproducir patrones numéricos crecientes mediante la suma y la multiplicación con números naturales hasta 999. (Ref. M.2.1.4.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.4', 'M.2.1.4. Describir y reproducir patrones numéricos crecientes con la suma y la multiplicación.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.5', 'Construir patrones de figuras basados en un atributo y patrones numéricos sencillos a partir de la suma y la resta. (Ref. M.2.1.5.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.5', 'Construir patrones de figuras basados en uno o dos atributos y patrones numéricos a partir de la suma, la resta y multiplicaciones sencillas. (Ref. M.2.1.5.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.5', 'M.2.1.5. Construir patrones de figuras basándose en sus atributos y patrones numéricos a partir de la suma, resta y multiplicación.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.6', 'Relacionar, mediante correspondencia uno a uno, elementos de un conjunto de salida con elementos de un conjunto de llegada, utilizando objetos o dibujos. (Ref. M.2.1.6.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.6', 'Relacionar los elementos de un conjunto de salida con los de un conjunto de llegada, a partir de correspondencias representadas con diagramas. (Ref. M.2.1.6.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.6', 'M.2.1.6. Relacionar los elementos del conjunto de salida con los elementos del conjunto de llegada, a partir de la correspondencia entre elementos.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.7', 'Representar parejas ordenadas sencillas de una correspondencia uno a uno entre los elementos de dos conjuntos, mediante diagramas y tablas. (Ref. M.2.1.7.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.7', 'Representar, en diagramas, tablas y una cuadrícula, parejas ordenadas de una relación entre los elementos de un conjunto de salida y uno de llegada. (Ref. M.2.1.7.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.7', 'M.2.1.7. Representar, en diagramas, tablas y una cuadrícula, las parejas ordenadas de una relación específica entre los elementos del conjunto de salida y los elementos del conjunto de llegada.', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'M.2.1.9', 'Representar por extensión pares ordenados sencillos obtenidos de la correspondencia entre elementos de dos conjuntos, con apoyo concreto o gráfico. (Ref. M.2.1.9.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'M.2.1.9', 'Representar por extensión y gráficamente pares ordenados del producto cartesiano AxB de conjuntos pequeños. (Ref. M.2.1.9.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'M.2.1.9', 'M.2.1.9. Representar por extensión y gráficamente los pares ordenados del producto cartesiano AxB.', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion), grupo = 1, activo = 1, updated_at = NOW();

INSERT INTO indicadores_evaluacion
    (materia_id, curriculo_id, grado, codigo, descripcion, grupo, activo, created_at, updated_at)
VALUES
(@materia_id, @curriculo_id, 2, 'I.M.2.1.1', 'Discrimina una propiedad de los objetos, forma conjuntos y obtiene subconjuntos sencillos de un conjunto universo, con apoyo concreto o gráfico. (Ref. I.M.2.1.1.) (S.2.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'I.M.2.1.1', 'Discrimina una o más propiedades de los objetos y obtiene subconjuntos de un conjunto universo, mediante representaciones gráficas. (Ref. I.M.2.1.1.) (S.2.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'I.M.2.1.1', 'I.M.2.1.1. Discrimina propiedades de los objetos y obtiene subconjuntos de un conjunto universo. (S.2.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'I.M.2.1.2', 'Propone y reproduce patrones sencillos de objetos y figuras según un atributo, y completa secuencias numéricas de suma o resta con apoyo concreto. (Ref. I.M.2.1.2.) (I.1.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'I.M.2.1.2', 'Propone patrones y construye series de objetos y figuras según sus atributos, y secuencias numéricas basadas en sumas y restas. (Ref. I.M.2.1.2.) (I.1.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'I.M.2.1.2', 'I.M.2.1.2. Propone patrones y construye series de objetos, figuras y secuencias numéricas. (I.1.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 2, 'I.M.2.1.3', 'Identifica y representa correspondencias uno a uno entre elementos de dos conjuntos mediante objetos, diagramas o tablas, y forma pares ordenados sencillos. (Ref. I.M.2.1.3.) (I.3., I.4.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 3, 'I.M.2.1.3', 'Discrimina, en diagramas, tablas y una cuadrícula, pares ordenados del producto cartesiano AxB que cumplen una relación uno a uno. (Ref. I.M.2.1.3.) (I.3., I.4.)', 1, 1, NOW(), NOW()),
(@materia_id, @curriculo_id, 4, 'I.M.2.1.3', 'I.M.2.1.3. Discrimina en diagramas, tablas y una cuadrícula los pares ordenados del producto cartesiano AxB que cumplen una relación uno a uno. (I.3., I.4.)', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion), grupo = 1, activo = 1, updated_at = NOW();

INSERT INTO competencias_curriculares (codigo, created_at, updated_at)
VALUES ('ES', NOW(), NOW()), ('C', NOW(), NOW()), ('CM', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

DELETE cd
FROM competencia_destreza cd
JOIN destrezas_curriculares d ON d.id = cd.destreza_curricular_id
WHERE d.materia_id = @materia_id
  AND d.curriculo_id = @curriculo_id
  AND d.grado IN (2, 3, 4)
  AND d.codigo IN ('M.2.1.1', 'M.2.1.2', 'M.2.1.3', 'M.2.1.4', 'M.2.1.5', 'M.2.1.6', 'M.2.1.7', 'M.2.1.9');

INSERT INTO competencia_destreza (competencia_curricular_id, destreza_curricular_id)
SELECT competencia.id, destreza.id
FROM destrezas_curriculares destreza
JOIN competencias_curriculares competencia
  ON competencia.codigo = CASE destreza.codigo
      WHEN 'M.2.1.1' THEN 'ES'
      WHEN 'M.2.1.2' THEN 'C'
      WHEN 'M.2.1.3' THEN 'CM'
      WHEN 'M.2.1.5' THEN 'CM'
      WHEN 'M.2.1.6' THEN 'ES'
      WHEN 'M.2.1.7' THEN 'CM'
  END
WHERE destreza.materia_id = @materia_id
  AND destreza.curriculo_id = @curriculo_id
  AND destreza.grado IN (2, 3, 4)
  AND destreza.codigo IN ('M.2.1.1', 'M.2.1.2', 'M.2.1.3', 'M.2.1.5', 'M.2.1.6', 'M.2.1.7');

DELETE di
FROM destreza_indicador di
JOIN destrezas_curriculares d ON d.id = di.destreza_curricular_id
WHERE d.materia_id = @materia_id
  AND d.curriculo_id = @curriculo_id
  AND d.grado IN (2, 3, 4)
  AND d.codigo IN ('M.2.1.1', 'M.2.1.2', 'M.2.1.3', 'M.2.1.4', 'M.2.1.5', 'M.2.1.6', 'M.2.1.7', 'M.2.1.9');

DELETE di
FROM destreza_indicador di
JOIN destrezas_curriculares d ON d.id = di.destreza_curricular_id
WHERE d.materia_id = @materia_id
  AND d.curriculo_id = @curriculo_anterior_id
  AND d.grado IN (2, 3, 4)
  AND d.codigo IN ('M.2.1.8', 'M.2.1.10');

INSERT INTO destreza_indicador (destreza_curricular_id, indicador_evaluacion_id)
SELECT destreza.id, indicador.id
FROM destrezas_curriculares destreza
JOIN indicadores_evaluacion indicador
  ON indicador.materia_id = destreza.materia_id
 AND indicador.curriculo_id = @curriculo_id
 AND indicador.grado = destreza.grado
 AND indicador.grupo = 1
 AND indicador.codigo IN ('I.M.2.1.1', 'I.M.2.1.2', 'I.M.2.1.3')
WHERE destreza.materia_id = @materia_id
  AND destreza.grado IN (2, 3, 4)
  AND (
      (destreza.curriculo_id = @curriculo_id
       AND destreza.codigo IN ('M.2.1.1', 'M.2.1.2', 'M.2.1.3', 'M.2.1.4', 'M.2.1.5', 'M.2.1.6', 'M.2.1.7', 'M.2.1.9'))
      OR
      (destreza.curriculo_id = @curriculo_anterior_id
       AND destreza.codigo IN ('M.2.1.8', 'M.2.1.10'))
  );

COMMIT;
