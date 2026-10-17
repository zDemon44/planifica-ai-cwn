---
paths:
  - 'app/**'
---

# App

## Preserve academic enrollment history
A student's course comes from currentEnrollment, never from the legacy_course reference. Do not infer academic periods from legacy text. Enrollments and section transfers preserve history; never cascade-delete students or academic catalogs with records. Route enrollment changes through ManageEnrollment so period/student locks and progression rules apply. Closed enrollments must not be overwritten to represent a later school year.

## Preserve academic enrollment history
El curso del estudiante se obtiene de matriculaVigente, nunca de curso_anterior. No inventar períodos a partir del texto antiguo. Conservar matrículas y cambios de paralelo; no borrar en cascada estudiantes o catálogos con historial. Usar GestionarMatricula para cambios académicos y mantener sus bloqueos y reglas de promoción. Una matrícula cerrada no se sobrescribe para representar otro año.

## Nombres del dominio en español
Usar Estudiante, Curso, PeriodoAcademico, Seccion, Matricula y CambioParalelo; tablas y atributos académicos también en español y sin tildes. Cada modelo declara su tabla explícitamente. Conservar los contratos técnicos de Laravel/Livewire (User, Auth, render, save, $fillable, created_at, updated_at). Las migraciones ya ejecutadas conservan sus nombres originales; los cambios se realizan con migraciones nuevas.

## Arquitectura tradicional para módulos nuevos
El usuario prefiere controladores y rutas propias para index, create, show y edit, con Blade index/form/show en carpetas por módulo. Reservar componentes para elementos repetidos como layout y sidebar. No convertir páginas nuevas en pantallas internas de un componente Livewire. Los contactos del representante permanecen como campos de Estudiante.

## Asistencia diaria por curso
La asistencia se registra una vez al día por sección, con estados asistencia, inasistencia y atraso. Respetar la matrícula y los cambios de paralelo en la fecha consultada. Conservar registros de períodos cerrados; no permitir cierres o traslados retroactivos que contradigan asistencias existentes.

## Roles y acceso del personal
Solo SUPERADMIN gestiona cuentas. ADMIN y SUPERADMIN gestionan el colegio. SUBINSPECTORA registra y consulta asistencia de todos los cursos; DOCENTE también puede hacerlo, pero solo en sus secciones asignadas. Vincular User.docente_id de forma única; conservar las contraseñas con hash en users. Verificar el estado y version_acceso en cada petición para invalidar sesiones al cambiar accesos.

## Tutoría necesaria para escribir asistencia docente
Sustituye el permiso docente anterior: un DOCENTE solo consulta asistencia de sus asignaciones o tutorías; registra y corrige únicamente su sección de tutoría. Una tutoría por docente y período, un tutor por sección, sin exigir materia asignada. ADMIN/SUPERADMIN administran tutorías; SUBINSPECTORA mantiene toda la asistencia. Períodos cerrados son inmutables. No convertir Tutor en un rol de cuenta.

## Justificaciones de faltas y atrasos
ADMIN, SUPERADMIN y SUBINSPECTORA registran justificaciones; docentes, incluidos tutores, solo consultan las de sus cursos. Conservar estado original, motivo, fecha y autor en DetalleAsistencia; no sobrescribir una justificación ni cambiar un estado ya justificado. Períodos cerrados y asistencias deshabilitadas son de consulta. Usar bloqueo de período y asistencia y aumentar version al justificar para invalidar formularios previos.

## Justificaciones docentes integradas en tutoría
Sustituye el alcance docente anterior: las justificaciones solo son visibles para el docente tutor de la sección, nunca por una asignación de materia. El docente las consulta en Asistencia de mi curso (vista=faltas), sin entrada independiente en sidebar; /justificaciones redirige allí. Aplicar ver-justificacion en detalles y enlaces. Administración y Subinspectora conservan gestión general.

## Cálculo de notas trimestrales
Esquema actual: tres trimestres. Promediar casa y clase por separado; original = (casa+clase)/2*0.70 + examen*0.30. Refuerzo solo para original completa menor que 7: (casa+clase+refuerzo)/3*0.70 + examen*0.30. Redondear finales a 2 decimales y aplicar refuerzo solo si mejora estrictamente; conservarlo internamente y omitirlo del boletín si no mejora. Pendientes son null, nunca cero; anual solo con tres finales completos. Docentes califican únicamente materias asignadas; tutoría sola no concede permiso. Períodos cerrados inmutables.

## Listado de notas sin filtro de asistencia
Para crear actividades de notas usar todas las matrículas de la sección, sin filtrar por asistencia ni fecha de matrícula. La fecha es un dato de la actividad y no recarga el listado. Al editar conservar el listado guardado para no alterar calificaciones históricas. Mantener validación de pertenencia al curso y permisos.

## Importación trimestral de actividades por Excel
La plantilla sigue excel de importacion.xlsx: estudiantes en filas, actividades nuevas en columnas y fecha, descripción, tarea y categoría arriba. Hasta 12 actividades por archivo. La importación crea actividades, no actualiza las existentes; vacío queda pendiente y cero es nota. Revisar antes de confirmar y guardar todo en una transacción mediante GuardarEvaluacion, refuerzo al final. Validar contexto cifrado, matrícula y versiones al leer y confirmar para impedir cruces de curso/trimestre, archivos obsoletos y reimportaciones.

## Importación Excel sin tope de actividades
Sustituye el anterior límite de 12 actividades: la plantilla trae 10 columnas iniciales (B:K), con hoja editable para copiar columnas adicionales a la derecha. Leer hasta la última columna con datos, ignorando columnas vacías, sin tope de cantidad propio; se conservan límites técnicos de tamaño del archivo. La carga, revisión y errores se muestran en un modal de Notas junto a Registrar actividad; confirmar por JSON mantiene guardado transaccional y permisos existentes.

## Panel docente según asignaciones y tutoría
/dashboard renderiza PortalDocente.panel para docentes, sin redirigir a Mis cursos. Mostrar solo secciones propias por materias o tutoría de períodos abiertos, agrupadas y con tutoría primero; Mis cursos conserva historial cerrado. La etiqueta superior Docente tutor depende de una tutoría en período abierto, sin nuevo rol ni cambios de permisos. La tutoría sola no muestra enlaces de notas de materias ajenas.

## Boletín completo para tutor y administración
El tutor de la sección y ADMIN/SUPERADMIN consultan el boletín completo por matrícula en boletines.show, incluso sin materia asignada; otros docentes conservan vista parcial de sus materias. No amplía permisos para editar notas. Boletín usa tres trimestres cuantitativos, refuerzo solo si mejora, firma public/images/firma-rectora.png en vista completa e impresión horizontal mediante navegador. Asistencia muestra totales del período, no por trimestre; solo registros activos de la matrícula. Cualitativas/comportamiento y supletorios quedan pendientes; no inferir equivalencias.

## Boletines como módulo independiente
Por preferencia del usuario, Boletines tiene entrada propia en sidebar, BoletinController y Boletines.index con selector de sección y estudiantes paginados. ADMIN/SUPERADMIN ven todos los cursos, docentes solo tutorías (incluido historial cerrado); no tutores no acceden al módulo completo. Reutilizar cálculo y vista imprimible existentes. Conservar accesos contextuales desde cursos y notas.

## Mis materias agrupa cursos por asignatura
El portal docente se llama Mis materias y agrupa por materia únicamente las asignaciones del docente autenticado; dentro de cada materia resaltar y priorizar su curso de tutoría. Conservar historial y permisos. Una tutoría sin materia sigue disponible en el panel y módulos de tutoría, no se inventa una materia. Las combinaciones de materias de los ejemplos no son restricciones de negocio.

## Planificación semanal y aprobación provisional
Cada planificación semanal pertenece a una Asignacion; docente, materia, sección y período se derivan de ella y no se copian como texto. Conservar esa asignación cuando tenga planificaciones. El docente dueño crea, edita borradores y envía; ADMIN/SUPERADMIN revisan y aprueban en etapas separadas hasta que se incorporen roles específicos. Los períodos cerrados y planes aprobados son de consulta.
