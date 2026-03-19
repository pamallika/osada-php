<?php

namespace Tests\Feature\Actions;

use App\Actions\Auth\LoginViaProviderAction;
use App\Actions\Auth\UpdateUserProfileAction;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_user_profile_action_saves_global_name()
    {
        $user = User::factory()->create();
        $action = new UpdateUserProfileAction();

        $action->execute($user, [
            'global_name' => 'New Global Name',
            'family_name' => 'TestFamily',
            'char_class' => 'Warrior',
            'attack' => 300,
            'awakening_attack' => 302,
            'defense' => 400,
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'global_name' => 'New Global Name',
            'family_name' => 'TestFamily',
        ]);
    }

    public function test_login_via_provider_action_fills_global_name_if_empty()
    {
        $action = new LoginViaProviderAction();

        $providerData = [
            'id' => '123456789',
            'username' => 'discord_user',
            'display_name' => 'Discord Global Name',
            'avatar' => 'avatar_hash',
        ];

        $action->execute('discord', $providerData);

        $user = User::first();
        $this->assertNotNull($user->profile);
        $this->assertEquals('Discord Global Name', $user->profile->global_name);

        // Test updating existing user with empty global_name
        $user->profile->update(['global_name' => '']);
        
        $providerData['display_name'] = 'Updated Global Name';
        $action->execute('discord', $providerData);

        $user->profile->refresh();
        $this->assertEquals('Updated Global Name', $user->profile->global_name);

        // Test not overwriting existing global_name
        $providerData['display_name'] = 'Should Not Overwrite';
        $action->execute('discord', $providerData);

        $user->profile->refresh();
        $this->assertEquals('Updated Global Name', $user->profile->global_name);
    }

    public function test_auth_me_initializes_empty_global_name()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'global_name' => '',
        ]);
    }

    public function test_update_profile_validation_for_global_name()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/v1/auth/profile', [
                'global_name' => str_repeat('a', 256), // Too long
                'family_name' => 'TestFamily',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['global_name']);
    }
}
