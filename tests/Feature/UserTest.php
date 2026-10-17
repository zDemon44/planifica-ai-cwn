<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_stores_email_hashed_password_and_default_role(): void
    {
        $user = User::factory()->create([
            'email' => 'colegio@gmail.com',
            'password' => 'Test-password-2026',
        ])->refresh();

        $this->assertSame('colegio@gmail.com', $user->email);
        $this->assertSame('usuario', $user->role);
        $this->assertTrue(Hash::check('Test-password-2026', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_role_can_be_assigned_explicitly(): void
    {
        $user = User::factory()->create();
        $user->role = 'docente';
        $user->save();

        $this->assertSame('docente', $user->fresh()->role);
    }
}
