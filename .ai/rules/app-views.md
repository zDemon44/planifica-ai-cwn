---
paths:
  - '{app/**,routes/**,resources/views/**}'
---

# App Views

## Sin módulo Mis materias: el docente entra por Planificaciones
Reemplaza la regla "Mis materias agrupa cursos por asignatura" y las menciones a Mis cursos. Se eliminó la vista Mis materias (PortalDocenteController y PortalDocente.index) por ser redundante; /mis-cursos redirige con 301 a /planificaciones. Las materias del docente, incluido el histórico de períodos cerrados, se muestran en Planificaciones.index y en el panel. No volver a crear una entrada de materias en el sidebar.
