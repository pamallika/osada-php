<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Test User',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => ['token', 'user']
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['token', 'user']
            ]);
    }

    public function test_user_cannot_access_protected_routes_without_onboarding()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        // Try to create a guild (protected by onboarded middleware)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/guilds', [
                'name' => 'My Guild',
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'ONBOARDING_REQUIRED']);
    }

    public function test_user_can_complete_onboarding()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/auth/profile', [
                'family_name' => 'TestFamily',
                'char_class' => 'Warrior',
                'attack' => 280,
                'awakening_attack' => 282,
                'defense' => 340,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'family_name' => 'TestFamily',
            'awakening_attack' => 282,
        ]);

        // Now should be able to access protected route
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/guilds', [
                'name' => 'My Guild',
                'logo_url' => 'http://example.com/logo.png',
            ]);

        // Guilds controller might return error if something else is wrong, but it shouldn't be 403 ONBOARDING_REQUIRED
        $response->assertStatus(201);
    }
}
