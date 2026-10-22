# Cambios SQL

Guardar cada cambio con el formato:

```text
AAAA_MM_DD_ORDEN_descripcion.sql
```

Ejemplo:

```text
2026_10_21_001_agregar_estado_planificacion.sql
```

`ORDEN` es un número consecutivo para los cambios realizados el mismo día: `001`, `002`, `003`, etc.

Los archivos se ejecutan en el orden de su nombre. Si un cambio ya fue compartido, no se modifica: se crea otro archivo con una fecha y hora nueva.

Usar como base [`AAAA_MM_DD_ORDEN_descripcion.sql`](AAAA_MM_DD_ORDEN_descripcion.sql).
