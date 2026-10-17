<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_displays_school_identity(): void
    {
        $this->get('/login')->assertSee('CARMEN WITHER NAVARRO')->assertSee('BIENVENIDO A PLANIFICA AI')->assertSee('images/logo-cwn.png');
    }

    public function test_user_can_login_and_remember_session(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_wrong_password_is_rejected_without_flashing_password(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.'])
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        $this->post('/login', [])->assertSessionHasErrors([
            'email' => 'Ingresa tu correo electrónico.',
            'password' => 'Ingresa tu contraseña.',
        ]);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->post('/login', ['email' => 'invalid', 'password' => ['invalid'], 'remember' => 'invalid'])
            ->assertSessionHasErrors([
                'email' => 'Ingresa un correo electrónico válido.',
                'password' => 'Ingresa una contraseña válida.',
                'remember' => 'La opción de recordar sesión no es válida.',
            ]);
    }

    public function test_email_must_be_a_string(): void
    {
        $this->post('/login', ['email' => ['invalid'], 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Ingresa un correo electrónico válido.']);
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos. Intenta nuevamente en un minuto.']);

        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_escapes_user_name_and_login_redirects_authenticated_users(): void
    {
        $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get('/dashboard')->assertSee($user->name)->assertDontSee($user->name, false);
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
