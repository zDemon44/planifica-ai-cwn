---
paths:
  - '{resources/views/Planificaciones/form.blade.php,app/Services/GeneradorPeriodoPlanificacion.php,app/Http/Controllers/AsistentePlanificacionController.php}'
---

# Controllers

## Copiloto con cambios pendientes y enfoque por campo
El copiloto es un panel único acoplado (sin fondo modal) con selector de bloque y de enfoque. Con `enfoque` el generador pide a Gemini solo ese campo y usa `contenido_actual` del bloque como contexto. Las propuestas se escriben en los campos como cambios pendientes con Mantener/Descartar (estilo Copilot); editar a mano o guardar los conserva. Nunca guardar en BD desde el copiloto.
