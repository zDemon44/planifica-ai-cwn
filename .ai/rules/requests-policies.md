---
paths:
  - 'app/{Models,Http/Controllers,Http/Requests,Policies}/**/*.php'
---

# Requests Policies

## Horario con docente responsable por curso
Durante la etapa de básica elemental, cada Horario pertenece a una sección y un docente responsable, con un solo curso por docente y período. Sus BloqueHorario definen materia, día y horas; al guardar, las materias del horario generan las Asignacion necesarias para que notas y planificaciones sigan usando la relación existente. Conservar conflictos que ya tengan planificaciones y bloquear el cambio.
