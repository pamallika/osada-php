<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Guild;
use App\Enums\GuildRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_player_profile(): void
    {
        $user = User::factory()->withProfile('Tester')->create();

        $guild = Guild::create([
            'name' => 'Test Guild',
            'slug' => 'test-guild',
            'owner_id' => $user->id
        ]);

        $user->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $otherUser = User::factory()->withProfile('Other', 'Mage')->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/{$otherUser->id}/profile");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'profile' => [
                        'family_name',
                        'char_class'
                    ]
                ]
            ]);
    }

    public function test_unauthorized_user_cannot_get_profile(): void
    {
        $user = User::factory()->create();
        $response = $this->getJson("/api/v1/users/{$user->id}/profile");

        $response->assertStatus(401);
    }

    public function test_returns_404_if_user_not_found(): void
    {
        $user = User::factory()->withProfile('Tester')->create();
        $guild = Guild::create(['name' => 'Test', 'slug' => 'test', 'owner_id' => $user->id]);
        $user->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/users/99999/profile");

        $response->assertStatus(404);
    }
}
