<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class UserResponseFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_update_returns_full_user_object()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => bcrypt('password')
        ]);
        $user->profile()->create([
            'global_name' => 'John Doe',
            'family_name' => 'DoeFamily',
            'char_class' => 'Warrior',
        ]);
        $user->linkedAccounts()->create([
            'provider' => 'discord',
            'provider_id' => '12345',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/auth/account', [
            'email' => 'new@example.com',
            'current_password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'email',
                    'profile' => [
                        'family_name',
                        'char_class'
                    ],
                    'linked_accounts' => [
                        '*' => [
                            'provider',
                            'provider_id'
                        ]
                    ]
                ]
            ])
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.profile.family_name', 'DoeFamily')
            ->assertJsonCount(1, 'data.linked_accounts');
    }

    public function test_unlink_account_returns_full_user_object()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->profile()->create([
            'global_name' => 'John Doe',
            'family_name' => 'DoeFamily',
            'char_class' => 'Warrior',
        ]);
        $user->linkedAccounts()->create([
            'provider' => 'discord',
            'provider_id' => '12345',
        ]);
        $user->linkedAccounts()->create([
            'provider' => 'telegram',
            'provider_id' => '67890',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/auth/linked-accounts/discord');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'profile',
                    'linked_accounts'
                ]
            ])
            ->assertJsonCount(1, 'data.linked_accounts')
            ->assertJsonPath('data.linked_accounts.0.provider', 'telegram');
    }
}
