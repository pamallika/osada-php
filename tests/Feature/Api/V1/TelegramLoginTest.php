<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class TelegramLoginTest extends TestCase
{
    use RefreshDatabase;

    protected $botToken = '123456789:ABCdefGHIjklMNOpqrSTUvwxYZ';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.telegram.bot_token', $this->botToken);
    }

    public function test_user_can_login_via_telegram_widget_new_user()
    {
        $data = [
            'id' => '12345678',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'photo_url' => 'http://example.com/photo.jpg',
            'auth_date' => time(),
        ];

        $data['hash'] = $this->generateHash($data);

        $response = $this->postJson('/api/v1/auth/telegram/login', $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'profile',
                        'linked_accounts'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('user_profiles', [
            'global_name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('linked_accounts', [
            'provider' => 'telegram',
            'provider_id' => '12345678',
            'username' => 'johndoe',
            'avatar' => 'http://example.com/photo.jpg',
        ]);
    }

    public function test_user_can_login_via_telegram_widget_existing_user()
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'global_name' => 'Old Name',
            'family_name' => '',
            'char_class' => 'None',
        ]);
        $user->linkedAccounts()->create([
            'provider' => 'telegram',
            'provider_id' => '12345678',
            'username' => 'olduser',
        ]);

        $data = [
            'id' => '12345678',
            'first_name' => 'New',
            'last_name' => 'Name',
            'username' => 'newuser',
            'photo_url' => 'http://example.com/new_photo.jpg',
            'auth_date' => time(),
        ];

        $data['hash'] = $this->generateHash($data);

        $response = $this->postJson('/api/v1/auth/telegram/login', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('linked_accounts', [
            'provider' => 'telegram',
            'provider_id' => '12345678',
            'username' => 'newuser',
            'display_name' => 'New Name',
            'avatar' => 'http://example.com/new_photo.jpg',
        ]);
    }

    public function test_telegram_login_fails_with_invalid_hash()
    {
        $data = [
            'id' => '12345678',
            'auth_date' => time(),
            'hash' => 'invalid_hash'
        ];

        $response = $this->postJson('/api/v1/auth/telegram/login', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hash']);
    }

    protected function generateHash(array $data): string
    {
        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash('sha256', $this->botToken, true);
        return hash_hmac('sha256', $dataCheckString, $secretKey);
    }
}
