<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Guild;
use App\Models\Event;
use App\Models\EventSquad;
use App\Models\EventParticipant;
use App\Models\LinkedAccount;
use App\Enums\GuildRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayNameApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_user_action_updates_global_name_in_profile(): void
    {
        $action = new \App\Actions\Discord\SyncUserAction();
        
        $data = [
            'discord_id' => '123456789',
            'username' => 'discord_user',
            'global_name' => 'Discord Hero',
            'avatar' => 'avatar_url'
        ];

        $user = $action->execute($data);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'global_name' => 'Discord Hero'
        ]);

        $this->assertDatabaseHas('linked_accounts', [
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_id' => '123456789',
            'display_name' => 'Discord Hero'
        ]);
    }

    public function test_api_resources_return_separated_names(): void
    {
        $user = User::factory()->withProfile('Skywalker', 'Anakin')->create();

        $guild = Guild::create(['name' => 'Jedi', 'slug' => 'jedi', 'owner_id' => $user->id]);
        $user->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $event = Event::create(['guild_id' => $guild->id, 'name' => 'Siege', 'start_at' => now(), 'status' => 'published', 'total_slots' => 20]);
        EventParticipant::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson("/api/v1/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.pending_users.0.family_name', 'Skywalker')
            ->assertJsonPath('data.pending_users.0.global_name', 'Anakin');
    }
}
