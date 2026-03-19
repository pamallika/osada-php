<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Guild;
use App\Models\Event;
use App\Enums\GuildRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_creation_stores_time_in_utc(): void
    {
        $owner = User::factory()->create();
        $owner->profile()->create(['family_name' => 'Owner']);
        $guild = Guild::create(['name' => 'Guild', 'slug' => 'guild', 'owner_id' => $owner->id]);
        $owner->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::CREATOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // 20:00 UTC+3 (Moscow) is 17:00 UTC
        $inputTime = '2026-03-10T20:00:00+03:00';
        $expectedUtcTime = '2026-03-10 17:00:00';

        $response = $this->actingAs($owner)
            ->postJson("/api/v1/events", [
                'name' => 'Timezone Test',
                'guild_id' => $guild->id,
                'start_at' => $inputTime,
                'total_slots' => 20
            ]);

        $response->assertStatus(200);
        
        $event = Event::first();
        // Database stores raw UTC string
        $this->assertEquals($expectedUtcTime, $event->getRawOriginal('start_at'));
    }

    public function test_event_update_stores_time_in_utc(): void
    {
        $owner = User::factory()->create();
        $owner->profile()->create(['family_name' => 'Owner']);
        $guild = Guild::create(['name' => 'Guild', 'slug' => 'guild', 'owner_id' => $owner->id]);
        $owner->guildMemberships()->create([
            'guild_id' => $guild->id,
            'role' => GuildRole::CREATOR,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $event = Event::create([
            'guild_id' => $guild->id,
            'name' => 'Initial',
            'start_at' => now(),
            'total_slots' => 20,
            'status' => 'draft'
        ]);

        // 21:00 UTC+3 is 18:00 UTC
        $inputTime = '2026-03-10T21:00:00+03:00';
        $expectedUtcTime = '2026-03-10 18:00:00';

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/events/{$event->id}", [
                'start_at' => $inputTime,
            ]);

        $response->assertStatus(200);
        
        $event->refresh();
        $this->assertEquals($expectedUtcTime, $event->getRawOriginal('start_at'));
    }
}
