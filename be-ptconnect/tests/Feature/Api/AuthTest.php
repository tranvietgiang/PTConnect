<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_login_with_email_success(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'access_token', 'refresh_token', 'token_type', 'expires_in',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ]);
    }

    public function test_login_with_username_success(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        $response->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_login_with_remember_me_sets_cookie(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
            'remember_me' => true,
        ]);

        $response->assertStatus(200);
        $cookies = $response->headers->getCookies();
        $rememberCookie = collect($cookies)->first(fn ($c) => $c->getName() === 'ptconnect_remember_token');
        $this->assertNotNull($rememberCookie);
        $this->assertTrue($rememberCookie->isHttpOnly());
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Thông tin đăng nhập không đúng.',
            ]);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Thông tin đăng nhập không đúng.',
            ]);
    }

    public function test_login_validation_fails_without_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_token_success(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Token refreshed successfully.'])
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_refresh_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid_token_here',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_refresh_fails_without_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', []);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_logout_success(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->postJson('/api/auth/logout', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Logout successful.']);
    }

    public function test_logout_without_token_still_succeeds(): void
    {
        $response = $this->postJson('/api/auth/logout', []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_me_with_valid_token(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $accessToken = $loginResponse->json('data.access_token');

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['user' => ['id' => $this->user->id, 'email' => 'admin@test.com', 'role' => 'admin']],
            ]);
    }

    public function test_me_public_endpoint(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_me_fails_without_token(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_protected_route_fails_without_token(): void
    {
        $response = $this->getJson('/api/classes');

        $response->assertStatus(401);
    }
}
