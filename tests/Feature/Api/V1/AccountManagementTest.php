<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\LinkedAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_email_and_password()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/auth/account', [
                'email' => 'new@example.com',
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_user_must_provide_correct_current_password_to_change_it()
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/auth/account', [
                'current_password' => 'WrongPassword',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_user_without_password_can_set_one_without_current_password()
    {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/auth/account', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200);

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_user_with_password_must_provide_it_to_change_email()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/v1/auth/account', [
                'email' => 'new@example.com',
                // missing current_password
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_user_can_unlink_social_account_if_password_is_set()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);
        
        $user->linkedAccounts()->create([
            'provider' => 'discord',
            'provider_id' => '12345',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/linked-accounts/discord');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('linked_accounts', [
            'user_id' => $user->id,
            'provider' => 'discord',
        ]);
    }

    public function test_user_cannot_unlink_last_auth_method_without_password()
    {
        $user = User::factory()->create([
            'password' => null, // No password set
        ]);
        
        $user->linkedAccounts()->create([
            'provider' => 'discord',
            'provider_id' => '12345',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/linked-accounts/discord');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
        
        $this->assertDatabaseHas('linked_accounts', [
            'user_id' => $user->id,
            'provider' => 'discord',
        ]);
    }

    public function test_user_can_unlink_one_social_account_if_another_exists()
    {
        $user = User::factory()->create([
            'password' => null,
        ]);
        
        $user->linkedAccounts()->create([
            'provider' => 'discord',
            'provider_id' => '12345',
        ]);

        $user->linkedAccounts()->create([
            'provider' => 'telegram',
            'provider_id' => '67890',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/auth/linked-accounts/discord');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('linked_accounts', [
            'user_id' => $user->id,
            'provider' => 'discord',
        ]);
        
        $this->assertDatabaseHas('linked_accounts', [
            'user_id' => $user->id,
            'provider' => 'telegram',
        ]);
    }
}
