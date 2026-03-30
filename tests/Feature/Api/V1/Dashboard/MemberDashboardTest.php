<?php

namespace Tests\Feature\Api\V1\Dashboard;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_get_dashboard_data()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $guild = Guild::factory()->create();
        
        // Active membership
        GuildMember::create([
            'user_id' => $user->id,
            'guild_id' => $guild->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        // Completed event user attended
        $completedEvent = Event::factory()->create([
            'guild_id' => $guild->id,
            'status' => 'completed',
            'start_at' => now()->subDay(),
        ]);
        EventParticipant::create([
            'event_id' => $completedEvent->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
        ]);

        // Next event user is participating in
        $nextEvent = Event::factory()->create([
            'guild_id' => $guild->id,
            'status' => 'published',
            'start_at' => now()->addDay(),
        ]);
        EventParticipant::create([
            'event_id' => $nextEvent->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Open event user is NOT yet a participant
        $openEvent = Event::factory()->create([
            'guild_id' => $guild->id,
            'status' => 'published',
            'start_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($user)->get('/api/v1/dashboard/member');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'sieges_attended' => 1,
                    ],
                    'guild' => [
                        'id' => $guild->id,
                    ],
                    'next_event' => [
                        'id' => $nextEvent->id,
                    ],
                ]
            ]);
        
        $response->assertJsonCount(1, 'data.open_events');
        $this->assertEquals($openEvent->id, $response->json('data.open_events.0.id'));
    }

    public function test_guest_cannot_access_dashboard()
    {
        $response = $this->getJson('/api/v1/dashboard/member');
        $response->assertStatus(401);
    }
}
