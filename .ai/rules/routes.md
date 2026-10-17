---
paths:
  - 'routes/**'
  - 'routes/**/*.php'
---

# Routes

## Rutas web separadas por módulo
routes/web.php es únicamente el cargador automático de routes/web/*.php. Cada módulo mantiene sus imports, middleware, orden interno de rutas específicas antes de parámetros dinámicos y nombres de ruta existentes; agregar módulos creando un archivo nuevo en esa carpeta.

## Rutas CRUD explícitas
Por preferencia del usuario, declarar cada ruta CRUD con Route::get, post, match o delete y su nombre visible. No usar Route::resource ni variantes abreviadas; conservar middleware, parámetros y nombres al expandirlas.
