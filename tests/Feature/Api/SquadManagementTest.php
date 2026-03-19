<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Guild;
use App\Models\Event;
use App\Models\EventSquad;
use App\Models\EventParticipant;
use App\Enums\GuildRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_can_move_participant_to_reserve(): void
    {
        // 1. Setup Officer
        $officer = User::factory()->create();
        $officer->profile()->create(['family_name' => 'Officer']);
        
        $guild = Guild::create(['name' => 'Test Guild', 'slug' => 'test-guild', 'owner_id' => $officer->id]);
        $officer->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::OFFICER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // 2. Setup Event and Squads
        $event = Event::create([
            'guild_id' => $guild->id,
            'name' => 'Siege',
            'start_at' => now()->addDay(),
            'status' => 'published',
            'total_slots' => 20
        ]);

        $mainSquad = EventSquad::create([
            'event_id' => $event->id,
            'title' => 'Main',
            'slots_limit' => 20,
            'is_system' => false
        ]);

        $reserveSquad = EventSquad::create([
            'event_id' => $event->id,
            'title' => 'Reserve',
            'slots_limit' => 0,
            'is_system' => true
        ]);

        // 3. Setup Participant in Main Squad
        $member = User::factory()->create();
        EventParticipant::create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'status' => 'confirmed',
            'squad_id' => $mainSquad->id
        ]);

        // 4. Act: Move to Reserve
        $response = $this->actingAs($officer)
            ->patchJson("/api/v1/events/{$event->id}/participants/{$member->id}", [
                'squad_id' => $reserveSquad->id
            ]);

        // 5. Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'user_id' => $member->id,
            'squad_id' => $reserveSquad->id
        ]);
    }

    public function test_regular_member_cannot_move_participants(): void
    {
        $member = User::factory()->create();
        $member->profile()->create(['family_name' => 'Member']);
        $guild = Guild::create(['name' => 'Test', 'slug' => 'test', 'owner_id' => User::factory()->create()->id]);
        $member->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $event = Event::create(['guild_id' => $guild->id, 'name' => 'Siege', 'start_at' => now(), 'status' => 'published', 'total_slots' => 20]);
        $otherMember = User::factory()->create();

        $response = $this->actingAs($member)
            ->patchJson("/api/v1/events/{$event->id}/participants/{$otherMember->id}", [
                'squad_id' => 1
            ]);

        $response->assertStatus(403);
    }

    public function test_officer_can_create_squad(): void
    {
        $officer = User::factory()->create();
        $officer->profile()->create(['family_name' => 'Officer']);
        
        $guild = Guild::create(['name' => 'Test Guild', 'slug' => 'test-guild', 'owner_id' => $officer->id]);
        $officer->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::OFFICER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $event = Event::create([
            'guild_id' => $guild->id,
            'name' => 'Siege',
            'start_at' => now()->addDay(),
            'status' => 'published',
            'total_slots' => 20
        ]);

        $response = $this->actingAs($officer)
            ->postJson("/api/v1/events/{$event->id}/squads", [
                'name' => 'New Squad',
                'limit' => 10
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_squads', [
            'event_id' => $event->id,
            'title' => 'New Squad',
            'slots_limit' => 10
        ]);
    }
}
