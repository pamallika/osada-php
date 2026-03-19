<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TelegramDeepLinkAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_initialize_telegram_deep_link_with_verifier_hash()
    {
        $verifierHash = hash('sha256', 'my-secret-verifier');

        $response = $this->postJson('/api/v1/auth/telegram/init', [
            'verifier_hash' => $verifierHash
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['auth_code']
            ]);

        $authCode = $response->json('data.auth_code');
        $cached = Cache::get('telegram_auth_code_' . $authCode);

        $this->assertIsArray($cached);
        $this->assertEquals('pending', $cached['status']);
        $this->assertEquals($verifierHash, $cached['verifier_hash']);
    }

    public function test_polling_returns_pending_when_not_confirmed()
    {
        $verifierHash = hash('sha256', 'my-secret-verifier');
        $code = 'test-code';
        Cache::put('telegram_auth_code_' . $code, [
            'status' => 'pending',
            'verifier_hash' => $verifierHash
        ]);

        $response = $this->getJson("/api/v1/auth/telegram/check/{$code}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => ['status' => 'pending']
            ]);
    }

    public function test_polling_returns_token_only_with_correct_verifier()
    {
        $user = User::factory()->create();
        $verifier = 'my-secret-verifier';
        $verifierHash = hash('sha256', $verifier);
        $code = 'test-code';
        
        // Simulate bot linking the user
        Cache::put('telegram_auth_code_' . $code, [
            'status' => $user->id,
            'verifier_hash' => $verifierHash
        ]);

        // Wrong verifier
        $response = $this->getJson("/api/v1/auth/telegram/check/{$code}?verifier=wrong");
        $response->assertStatus(404);

        // No verifier
        $response = $this->getJson("/api/v1/auth/telegram/check/{$code}");
        $response->assertStatus(200)
            ->assertJson([
                'data' => ['status' => 'pending']
            ]);

        // Correct verifier
        $response = $this->getJson("/api/v1/auth/telegram/check/{$code}?verifier={$verifier}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user'
                ]
            ]);
        
        $this->assertNull(Cache::get('telegram_auth_code_' . $code));
    }
}
