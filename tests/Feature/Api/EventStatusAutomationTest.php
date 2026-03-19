<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Guild;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Enums\GuildRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_event_creates_pending_participants_for_active_members(): void
    {
        // 1. Setup Guild and Members
        $owner = User::factory()->create();
        $owner->profile()->create(['family_name' => 'Owner']);
        $guild = Guild::create(['name' => 'Automated Guild', 'slug' => 'auto-guild', 'owner_id' => $owner->id]);
        
        $owner->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::CREATOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $activeMember = User::factory()->create();
        $activeMember->profile()->create(['family_name' => 'Active']);
        $activeMember->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $pendingMember = User::factory()->create();
        $pendingMember->profile()->create(['family_name' => 'PendingM']);
        $pendingMember->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'pending', // Не должен попасть в список
            'joined_at' => now(),
        ]);

        // 2. Setup Event in Draft
        $event = Event::create([
            'guild_id' => $guild->id,
            'name' => 'Test Event',
            'start_at' => now()->addDay(),
            'status' => 'draft',
            'total_slots' => 20
        ]);

        // 3. Act: Publish as Officer
        $response = $this->actingAs($owner)
            ->postJson("/api/v1/events/{$event->id}/publish");

        // 4. Assert
        $response->assertStatus(200);
        
        // Должны быть записи для owner и activeMember
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'status' => 'pending'
        ]);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'user_id' => $activeMember->id,
            'status' => 'pending'
        ]);
        
        // Не должно быть записи для pendingMember
        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'user_id' => $pendingMember->id
        ]);
    }

    public function test_publishing_event_does_not_duplicate_existing_participants(): void
    {
        $owner = User::factory()->create();
        $owner->profile()->create(['family_name' => 'Owner']);
        $guild = Guild::create(['name' => 'Auto', 'slug' => 'auto', 'owner_id' => $owner->id]);
        $owner->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::CREATOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $event = Event::create([
            'guild_id' => $guild->id,
            'name' => 'Test',
            'start_at' => now(),
            'status' => 'draft',
            'total_slots' => 20
        ]);

        // Участник уже есть (например, сам записался в черновик)
        EventParticipant::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'status' => 'confirmed' // Должен остаться confirmed
        ]);

        $this->actingAs($owner)->postJson("/api/v1/events/{$event->id}/publish");

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'status' => 'confirmed'
        ]);
        $this->assertEquals(1, EventParticipant::where('event_id', $event->id)->where('user_id', $owner->id)->count());
    }

    public function test_only_officers_can_publish_events(): void
    {
        $member = User::factory()->create();
        $member->profile()->create(['family_name' => 'Member']);
        $guild = Guild::create(['name' => 'Guild', 'slug' => 'guild', 'owner_id' => User::factory()->create()->id]);
        $member->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::MEMBER,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $event = Event::create(['guild_id' => $guild->id, 'name' => 'E', 'start_at' => now(), 'status' => 'draft', 'total_slots' => 20]);

        $response = $this->actingAs($member)->postJson("/api/v1/events/{$event->id}/publish");

        $response->assertStatus(403);
    }
}
