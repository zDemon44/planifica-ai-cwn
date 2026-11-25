---
paths:
  - '{resources/views/Planificaciones/form.blade.php,app/Services/GeneradorPeriodoPlanificacion.php,app/Http/Controllers/AsistentePlanificacionController.php}'
---

# Controllers

## Copiloto con cambios pendientes y enfoque por campo
El copiloto es un panel único acoplado (sin fondo modal) con selector de bloque y de enfoque. Con `enfoque` el generador pide a Gemini solo ese campo y usa `contenido_actual` del bloque como contexto. Las propuestas se escriben en los campos como cambios pendientes con Mantener/Descartar (estilo Copilot); editar a mano o guardar los conserva. Nunca guardar en BD desde el copiloto.

## Copiloto elige destreza e indicador con tema libre
Si el bloque tiene tema libre y no tiene destreza ni indicador, el copiloto llama primero a seleccionarCurriculo(): Gemini elige entre los IDs del catálogo (enum D{id}/I{id}, destrezas activas de la materia y grado con indicadores vinculados, priorizando el Currículo priorizado 2025) y el servidor verifica el vínculo (reintenta una vez). La selección vuelve como `seleccion` y se aplica a los selects como cambio pendiente con Mantener/Descartar. Cuesta una solicitud extra a Gemini.

## Tema libre sin texto también habilita el copiloto
Actualiza la regla de selección automática: en modo tema libre el copiloto siempre se habilita aunque no haya tema ni indicaciones. La IA elige destreza e indicador según el tema, si no según las indicaciones, si no según el título de la unidad, y en modo bloque completo propone también el tema. No volver a exigir tema escrito.
