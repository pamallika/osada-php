<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Guild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_guild_if_onboarded()
    {
        $user = User::factory()->create();
        $user->profile()->create(['family_name' => 'TestFamily']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/guilds', [
                'name' => 'New Guild',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Guild');

        $this->assertDatabaseHas('guilds', ['name' => 'New Guild']);
        $this->assertDatabaseHas('guild_members', [
            'user_id' => $user->id,
            'role' => 'creator',
        ]);
    }

    public function test_user_cannot_create_two_guilds()
    {
        $user = User::factory()->create();
        $user->profile()->create(['family_name' => 'TestFamily']);
        $token = $user->createToken('test')->plainTextToken;

        // First guild
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/guilds', [
                'name' => 'First Guild',
            ]);

        // Second guild
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/guilds', [
                'name' => 'Second Guild',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_cannot_create_guild_if_already_member()
    {
        $user = User::factory()->create();
        $user->profile()->create(['family_name' => 'TestFamily']);
        $token = $user->createToken('test')->plainTextToken;

        $otherGuild = Guild::factory()->create(['owner_id' => User::factory()->create()->id]);
        $otherGuild->members()->create(['user_id' => $user->id, 'role' => 'member']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/guilds', [
                'name' => 'My Own Guild',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
