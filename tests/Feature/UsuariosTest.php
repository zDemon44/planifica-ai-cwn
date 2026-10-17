<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function superadmin(): User
    {
        $usuario = User::factory()->create(['role' => 'SUPERADMIN']);
        $this->actingAs($usuario);

        return $usuario;
    }

    private function datos(string $role = 'ADMIN'): array
    {
        return ['name' => 'Cuenta de prueba', 'email' => 'cuenta@example.com', 'password' => 'ClaveNueva123', 'password_confirmation' => 'ClaveNueva123', 'role' => $role, 'activo' => 1, 'docente_id' => null];
    }

    public function test_superadmin_crea_edita_y_consulta_cuentas(): void
    {
        $this->superadmin();
        $datos = $this->datos();
        $this->get('/usuarios/create')->assertSee('Guardar usuario');
        $this->post('/usuarios', $datos)->assertSessionHasNoErrors();
        $usuario = User::where('email', $datos['email'])->sole();
        $this->assertTrue(Hash::check('ClaveNueva123', $usuario->password));
        $this->get('/usuarios')->assertSee($usuario->email);
        $this->get(route('usuarios.show', $usuario))->assertSee('Administrador')->assertDontSee($usuario->password);
        $this->put(route('usuarios.update', $usuario), [...$datos, 'name' => 'Editado', 'password' => '', 'password_confirmation' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Editado']);
    }

    public function test_solo_superadmin_gestiona_cuentas(): void
    {
        $usuario = User::factory()->create();
        $this->get('/usuarios')->assertRedirect('/login');
        foreach (['ADMIN', 'DOCENTE'] as $role) {
            $docente = $role === 'DOCENTE' ? Docente::factory()->create() : null;
            $this->actingAs(User::factory()->create(['role' => $role, 'docente_id' => $docente?->id]));
            $this->get('/usuarios')->assertForbidden();
            $this->get('/usuarios/create')->assertForbidden();
            $this->get(route('usuarios.show', $usuario))->assertForbidden();
            $this->post('/usuarios', $this->datos())->assertForbidden();
        }
    }

    public function test_valida_rol_password_y_vinculo_docente_unico(): void
    {
        $this->superadmin();
        $datos = $this->datos('DOCENTE');
        $this->post('/usuarios', $datos)->assertSessionHasErrors('docente_id');
        $this->post('/usuarios', [...$datos, 'role' => 'ROL_INEXISTENTE', 'password_confirmation' => 'otra'])->assertSessionHasErrors(['role', 'password']);
        $docente = Docente::factory()->create();
        $datos['docente_id'] = $docente->id;
        $this->post('/usuarios', $datos)->assertSessionHasNoErrors();
        $this->post('/usuarios', [...$datos, 'email' => 'otra@example.com'])->assertSessionHasErrors('docente_id');
    }

    public function test_superadmin_no_puede_desactivarse_o_quitarse_el_rol(): void
    {
        $usuario = $this->superadmin();
        $datos = [...$this->datos('SUPERADMIN'), 'email' => $usuario->email];
        $this->put(route('usuarios.update', $usuario), [...$datos, 'activo' => 0])->assertSessionHasErrors('role');
        $this->put(route('usuarios.update', $usuario), [...$datos, 'role' => 'ADMIN'])->assertSessionHasErrors('role');
        $this->assertTrue($usuario->fresh()->activo);
    }

    public function test_desactivar_y_restablecer_password_revoca_acceso_anterior(): void
    {
        $this->superadmin();
        $usuario = User::factory()->create(['role' => 'ADMIN']);
        $this->put(route('usuarios.update', $usuario), [...$this->datos(), 'email' => $usuario->email, 'activo' => 0])->assertSessionHasNoErrors();
        $this->withSession(['version_acceso' => 1])->actingAs($usuario->fresh())->get('/dashboard')->assertRedirect('/login');
        $usuario->update(['activo' => true]);
        $this->post('/login', ['email' => $usuario->email, 'password' => 'ClaveNueva123'])->assertRedirect('/dashboard');
    }

    public function test_docente_solo_ve_sus_materias_y_planificaciones(): void
    {
        $asignacion = Asignacion::factory()->create();
        $otra = Asignacion::factory()->create();
        $usuario = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $asignacion->docente_id]);
        $this->actingAs($usuario);

        $this->get('/dashboard')->assertOk()->assertViewIs('PortalDocente.panel')->assertSee($asignacion->materia->nombre)->assertDontSee($otra->materia->nombre);
        $this->get('/mis-cursos')->assertSee($asignacion->materia->nombre)->assertDontSee($otra->materia->nombre);
        $this->get(route('planificaciones.index'))->assertOk()->assertSee($asignacion->materia->nombre)->assertDontSee($otra->materia->nombre);
        $this->get('/docentes')->assertForbidden();
        $this->get('/academico')->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
    }

    public function test_superadmin_mantiene_acceso_y_docente_inactivo_no_ingresa(): void
    {
        $this->superadmin();
        foreach (['academico', 'docentes', 'materias', 'asignaciones', 'horarios', 'recreos', 'planificaciones'] as $ruta) {
            $this->get('/'.$ruta)->assertOk();
        }
        $docente = Docente::factory()->create(['estado' => 'inactivo']);
        $usuario = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $docente->id]);
        auth()->logout();
        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])->assertSessionHasErrors('email');
    }

    public function test_puede_desactivar_cuenta_de_docente_inactivo_y_conservar_su_ficha(): void
    {
        $this->superadmin();
        $docente = Docente::factory()->create(['estado' => 'inactivo']);
        $usuario = User::factory()->create(['role' => 'DOCENTE', 'docente_id' => $docente->id]);

        $this->put(route('usuarios.update', $usuario), [...$this->datos('DOCENTE'), 'email' => $usuario->email, 'docente_id' => $docente->id, 'activo' => 0])->assertSessionHasNoErrors();
        $this->delete(route('docentes.destroy', $docente))->assertSessionHasErrors('registro');
        $this->assertFalse($usuario->fresh()->activo);
        $this->assertModelExists($docente);
    }

    public function test_promover_cuenta_existente_conserva_su_password(): void
    {
        $usuario = User::factory()->create(['role' => 'ADMIN']);

        $this->artisan('usuario:superadmin', ['email' => $usuario->email])->assertSuccessful();
        $this->assertSame('SUPERADMIN', $usuario->fresh()->role);
        $this->assertSame($usuario->password, $usuario->fresh()->password);
    }
}
