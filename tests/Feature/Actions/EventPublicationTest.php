<?php

namespace Tests\Feature\Actions;

use App\Actions\Events\Core\UpdateEventStatusAction;
use App\Models\Event;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_publication_creates_pending_participants_for_all_active_members()
    {
        $guild = Guild::factory()->create();
        
        // Create 3 active members
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            GuildMember::create([
                'guild_id' => $guild->id,
                'user_id' => $user->id,
                'role' => 'member',
                'status' => 'active',
            ]);
        }

        // Create 1 pending member (should not be included)
        $pendingUser = User::factory()->create();
        GuildMember::create([
            'guild_id' => $guild->id,
            'user_id' => $pendingUser->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $event = Event::factory()->create([
            'guild_id' => $guild->id,
            'status' => 'draft',
        ]);

        $action = new UpdateEventStatusAction();
        $action->execute($event, 'published');

        $this->assertEquals('published', $event->fresh()->status);
        
        // Check that 3 pending participants were created
        $this->assertDatabaseCount('event_participants', 3);
        foreach ($users as $user) {
            $this->assertDatabaseHas('event_participants', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
        }

        // Check that pending user is NOT in the list
        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'user_id' => $pendingUser->id,
        ]);
    }

    public function test_event_publication_does_not_overwrite_existing_participants()
    {
        $guild = Guild::factory()->create();
        $user = User::factory()->create();
        GuildMember::create([
            'guild_id' => $guild->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        $event = Event::factory()->create([
            'guild_id' => $guild->id,
            'status' => 'draft',
        ]);

        // Existing participant (e.g. joined manually before publication)
        $event->participants()->create([
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        $action = new UpdateEventStatusAction();
        $action->execute($event, 'published');

        // Should still be confirmed
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);
    }
}
