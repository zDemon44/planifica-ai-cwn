<?php

namespace Tests\Feature;

use App\Livewire\Academico\Catalogo;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocenciaTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN']));
    }

    public function test_crud_docentes_con_rutas_y_vistas_propias(): void
    {
        $this->admin();
        $datos = Docente::factory()->make()->toArray();
        $this->get('/docentes/create')->assertSee('Guardar docente');
        $this->post('/docentes', $datos)->assertSessionHasNoErrors();
        $docente = Docente::sole();
        $this->get('/docentes')->assertSee($datos['identificacion']);
        $this->get('/docentes/'.$docente->id)->assertSee($datos['correo']);
        $this->get('/docentes/'.$docente->id.'/edit')->assertSee($datos['identificacion']);
        $this->put('/docentes/'.$docente->id, [...$datos, 'nombres' => 'Actualizado'])->assertRedirect(route('docentes.show', $docente));
        $this->assertDatabaseHas('docentes', ['id' => $docente->id, 'nombres' => 'Actualizado']);
        $this->delete('/docentes/'.$docente->id)->assertRedirect(route('docentes.index'));
        $this->assertModelMissing($docente);
    }

    public function test_crud_materias_con_validacion_y_escape(): void
    {
        $this->admin();
        $datos = ['nombre' => 'Matemáticas', 'descripcion' => '<script>alert(1)</script>', 'estado' => 'activo'];
        $this->get('/materias/create')->assertSee('Guardar materia');
        $this->post('/materias', $datos)->assertSessionHasNoErrors();
        $materia = Materia::sole();
        $this->get('/materias')->assertSee('Matemáticas');
        $this->get('/materias/'.$materia->id)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/materias/'.$materia->id.'/edit')->assertSee('Matemáticas');
        $this->put('/materias/'.$materia->id, [...$datos, 'nombre' => 'Álgebra'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materias', ['id' => $materia->id, 'nombre' => 'Álgebra']);
        $this->delete('/materias/'.$materia->id)->assertRedirect(route('materias.index'));
        $this->assertModelMissing($materia);
    }

    public function test_un_docente_puede_tener_varias_materias_y_cursos(): void
    {
        $this->admin();
        $docente = Docente::factory()->create();
        $materias = Materia::factory()->count(2)->create();
        $secciones = Seccion::factory()->count(2)->create();
        $datos = ['docente_id' => $docente->id, 'materia_id' => $materias[0]->id, 'seccion_id' => $secciones[0]->id];

        $this->get('/asignaciones/create')->assertSee('Guardar asignación');
        $this->post('/asignaciones', $datos)->assertSessionHasNoErrors();
        $this->post('/asignaciones', [...$datos, 'materia_id' => $materias[1]->id])->assertSessionHasNoErrors();
        $this->post('/asignaciones', [...$datos, 'seccion_id' => $secciones[1]->id])->assertSessionHasNoErrors();
        $asignacion = Asignacion::first();
        $this->get('/asignaciones')->assertSee($docente->apellidos)->assertSee($materias[0]->nombre);
        $this->get(route('asignaciones.show', $asignacion))->assertSee($secciones[0]->curso->nombre);
        $this->get(route('asignaciones.edit', $asignacion))->assertSee('Guardar asignación');
        $this->post('/asignaciones', $datos)->assertSessionHasErrors('seccion_id');
        $this->assertDatabaseCount('asignaciones', 3);

        $nuevo = Docente::factory()->create();
        $this->put(route('asignaciones.update', $asignacion), [...$datos, 'docente_id' => $nuevo->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asignaciones', ['id' => $asignacion->id, 'docente_id' => $nuevo->id]);
        $this->delete(route('asignaciones.destroy', $asignacion))->assertRedirect(route('asignaciones.index'));
        $this->assertModelMissing($asignacion);
    }

    public function test_periodo_cerrado_conserva_historial_y_el_siguiente_crea_otro_registro(): void
    {
        $this->admin();
        $anterior = Asignacion::factory()->create();
        $anterior->seccion->periodo->update(['cerrado' => true]);
        $periodo = PeriodoAcademico::factory()->create(['nombre' => '2027-2028', 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2028-03-01']);
        $seccion = Seccion::factory()->create(['curso_id' => $anterior->seccion->curso_id, 'periodo_academico_id' => $periodo->id]);
        $datos = ['docente_id' => $anterior->docente_id, 'materia_id' => $anterior->materia_id, 'seccion_id' => $seccion->id];

        $this->put(route('asignaciones.update', $anterior), $datos)->assertSessionHasErrors('seccion_id');
        $this->get(route('asignaciones.edit', $anterior))->assertForbidden();
        $this->delete(route('asignaciones.destroy', $anterior))->assertSessionHasErrors('registro');
        $this->post('/asignaciones', [...$datos, 'seccion_id' => $anterior->seccion_id])->assertSessionHasErrors('seccion_id');
        $this->post('/asignaciones', $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asignaciones', ['id' => $anterior->id, 'seccion_id' => $anterior->seccion_id]);
        $this->assertDatabaseCount('asignaciones', 2);
        $this->get(route('asignaciones.show', $anterior))->assertSee('Período cerrado');
    }

    public function test_no_se_eliminan_catalogos_con_asignaciones(): void
    {
        $this->admin();
        $asignacion = Asignacion::factory()->create();

        $this->delete(route('docentes.destroy', $asignacion->docente))->assertSessionHasErrors('registro');
        $this->delete(route('materias.destroy', $asignacion->materia))->assertSessionHasErrors('registro');
        Livewire::test(Catalogo::class)->call('tab', 'secciones')->call('confirmarEliminacion', $asignacion->seccion_id)->assertHasErrors('catalogo');
        Livewire::test(Catalogo::class)->call('tab', 'secciones')->call('edit', $asignacion->seccion_id)->set('formulario.nombre', 'Otro')->call('save')->assertHasErrors('catalogo');
        $this->assertModelExists($asignacion->docente);
        $this->assertModelExists($asignacion->materia);
        $this->assertSame($asignacion->seccion->nombre, $asignacion->seccion->fresh()->nombre);
    }

    public function test_solo_docentes_y_materias_activos_reciben_asignaciones(): void
    {
        $this->admin();
        $docente = Docente::factory()->create(['estado' => 'inactivo']);
        $materia = Materia::factory()->create();
        $seccion = Seccion::factory()->create();
        $datos = ['docente_id' => $docente->id, 'materia_id' => $materia->id, 'seccion_id' => $seccion->id];

        $this->post('/asignaciones', $datos)->assertSessionHasErrors('docente_id');
        $docente->update(['estado' => 'activo']);
        $materia->update(['estado' => 'inactivo']);
        $this->post('/asignaciones', $datos)->assertSessionHasErrors('docente_id');
        $this->assertDatabaseCount('asignaciones', 0);
    }

    public function test_valida_campos_identificaciones_y_materias_duplicadas(): void
    {
        $this->admin();
        $docente = Docente::factory()->create();
        $materia = Materia::factory()->create();

        $this->post('/docentes', [])->assertSessionHasErrors(['nombres', 'apellidos', 'identificacion', 'estado']);
        $this->post('/docentes', [...Docente::factory()->make()->toArray(), 'identificacion' => $docente->identificacion, 'correo' => 'invalido', 'estado' => 'incorrecto'])->assertSessionHasErrors(['identificacion', 'correo', 'estado']);
        $this->post('/materias', ['nombre' => $materia->nombre, 'estado' => 'activo'])->assertSessionHasErrors('nombre');
        $this->post('/materias', ['nombre' => '', 'estado' => 'otro'])->assertSessionHasErrors(['nombre', 'estado']);
        $this->post('/asignaciones', ['docente_id' => 999999, 'materia_id' => 999999, 'seccion_id' => 999999])->assertSessionHasErrors(['docente_id', 'materia_id', 'seccion_id']);
        $this->assertDatabaseCount('docentes', 1);
        $this->assertDatabaseCount('materias', 1);
        $this->assertDatabaseCount('asignaciones', 0);
    }

    public function test_todas_las_rutas_requieren_administrador(): void
    {
        $asignacion = Asignacion::factory()->create();
        foreach (['docentes' => $asignacion->docente, 'materias' => $asignacion->materia, 'asignaciones' => $asignacion] as $recurso => $modelo) {
            $this->get('/'.$recurso)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach (['docentes' => $asignacion->docente, 'materias' => $asignacion->materia, 'asignaciones' => $asignacion] as $recurso => $modelo) {
            $this->get('/'.$recurso)->assertForbidden();
            $this->get('/'.$recurso.'/create')->assertForbidden();
            $this->get('/'.$recurso.'/'.$modelo->id)->assertForbidden();
            $this->get('/'.$recurso.'/'.$modelo->id.'/edit')->assertForbidden();
            $this->post('/'.$recurso, [])->assertForbidden();
            $this->put('/'.$recurso.'/'.$modelo->id, [])->assertForbidden();
            $this->delete('/'.$recurso.'/'.$modelo->id)->assertForbidden();
            $this->assertModelExists($modelo);
        }
    }

    public function test_filtros_y_rutas_inexistentes(): void
    {
        $this->admin();
        $asignacion = Asignacion::factory()->create();
        $otro = Asignacion::factory()->create();
        $this->get('/asignaciones?docente_id='.$asignacion->docente_id.'&periodo_id='.$asignacion->seccion->periodo_academico_id)->assertSee($asignacion->materia->nombre)->assertDontSee($otro->materia->nombre);
        $this->get('/docentes?busqueda='.$asignacion->docente->identificacion)->assertSee($asignacion->docente->identificacion)->assertDontSee($otro->docente->identificacion);
        foreach (['docentes', 'materias', 'asignaciones'] as $recurso) {
            $this->get('/'.$recurso.'/999999')->assertNotFound();
        }
    }
}
