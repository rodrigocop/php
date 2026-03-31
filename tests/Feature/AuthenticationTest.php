<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_active_user(): void
    {
        $user = User::factory()->create([
            'email' => 'activo@example.com',
            'password' => 'secret1234',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'activo@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email', 'role']]);
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertSame($user->email, $response->json('user.email'));
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'correct',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_rejects_inactive_user(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactivo@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactivo@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertForbidden();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }
}
