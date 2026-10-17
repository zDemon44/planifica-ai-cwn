---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Visibilidad explícita por rol en Blade
Por preferencia del usuario, mostrar u ocultar controles generales con condiciones explícitas sobre auth()->user()->role para que la vista sea fácil de seguir. Conservar policies y authorize() en el backend como protección real, e incluir SUPERADMIN cuando corresponda a ADMIN.
