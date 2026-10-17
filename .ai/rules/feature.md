---
paths:
  - 'app/{Actions,Models,Http/Controllers,Http/Requests}/**/*.php,resources/views/Planificaciones/**,tests/Feature/PlanificacionesTest.php'
---

# Feature

## Bloques semanales de planificación derivados del horario
Cada clase del horario equivale a 45 minutos. Para una planificación, agrupar únicamente bloques de la misma materia, día y con hora_fin igual a la siguiente hora_inicio; un intervalo o recreo inicia otro bloque. Cada bloque conserva sus propios campos pedagógicos y la cantidad total de períodos se calcula en el servidor desde el horario.

## Bloques semanales de planificación derivados del horario
Cada clase del horario equivale a 45 minutos. Agrupar bloques de la misma materia y día cuando son contiguos; si el único intervalo entre ambos es el recreo institucional configurado, también forman un solo bloque pedagógico y el recreo no suma minutos. Cada bloque conserva sus propios campos pedagógicos y el total de períodos se calcula en el servidor.

## El recreo no divide un bloque pedagógico
Esta regla reemplaza cualquier nota anterior que indicara que el recreo separa bloques. Dos clases de la misma materia y día, ubicadas inmediatamente antes y después del recreo institucional configurado, comparten un solo conjunto pedagógico; cuentan como dos períodos de 45 minutos.
