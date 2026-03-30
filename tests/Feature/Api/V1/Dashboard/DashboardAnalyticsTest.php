<?php

namespace Tests\Feature\Api\V1\Dashboard;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $role, ?Guild $guild = null): User
    {
        $user = User::factory()->create();
        $guild = $guild ?? Guild::factory()->create();
        
        GuildMember::create([
            'user_id' => $user->id,
            'guild_id' => $guild->id,
            'role' => $role,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_member_cannot_access_analytics()
    {
        $user = $this->createUserWithRole('member');

        $response = $this->actingAs($user)->getJson('/api/v1/dashboard/analytics');

        $response->assertStatus(403);
    }

    public function test_officer_can_get_analytics_but_no_hr_data()
    {
        $guild = Guild::factory()->create();
        $user = $this->createUserWithRole('officer', $guild);

        $response = $this->actingAs($user)->getJson('/api/v1/dashboard/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'activity' => ['fill_rate', 'top_players'],
                    'meta' => ['class_distribution']
                ]
            ])
            ->assertJsonMissingPath('data.hr');
    }

    public function test_admin_can_get_analytics_with_hr_data()
    {
        $guild = Guild::factory()->create();
        $user = $this->createUserWithRole('admin', $guild);

        $response = $this->actingAs($user)->getJson('/api/v1/dashboard/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'hr' => ['dynamics' => ['dates', 'joined', 'left']]
                ]
            ]);
    }

    public function test_fill_rate_calculation_is_correct()
    {
        $guild = Guild::factory()->create();
        $user = $this->createUserWithRole('officer', $guild);

        // Event 1: 10 slots, 5 confirmed -> 50%
        $event1 = Event::factory()->create([
            'guild_id' => $guild->id,
            'total_slots' => 10,
            'status' => 'completed',
            'start_at' => now()->subDays(2),
        ]);
        
        for ($i = 0; $i < 5; $i++) {
            EventParticipant::factory()->create([
                'event_id' => $event1->id,
                'status' => 'confirmed'
            ]);
        }

        // Event 2: 20 slots, 15 confirmed -> 75%
        // Total slots: 30, Total confirmed: 20 -> 66.7%
        $event2 = Event::factory()->create([
            'guild_id' => $guild->id,
            'total_slots' => 20,
            'status' => 'published',
            'start_at' => now()->subDays(1),
        ]);
        
        for ($i = 0; $i < 15; $i++) {
            EventParticipant::factory()->create([
                'event_id' => $event2->id,
                'status' => 'confirmed'
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/v1/dashboard/analytics?period=7');

        $response->assertStatus(200)
            ->assertJsonPath('data.activity.fill_rate', 66.7);
    }

    public function test_class_distribution_is_correct()
    {
        $guild = Guild::factory()->create();
        $admin = $this->createUserWithRole('admin', $guild);

        // User 1: Warrior
        $u1 = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $u1->id, 'char_class' => 'Warrior']);
        GuildMember::create(['guild_id' => $guild->id, 'user_id' => $u1->id, 'status' => 'active']);

        // User 2: Warrior
        $u2 = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $u2->id, 'char_class' => 'Warrior']);
        GuildMember::create(['guild_id' => $guild->id, 'user_id' => $u2->id, 'status' => 'active']);

        // User 3: Mage
        $u3 = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $u3->id, 'char_class' => 'Mage']);
        GuildMember::create(['guild_id' => $guild->id, 'user_id' => $u3->id, 'status' => 'active']);

        $response = $this->actingAs($admin)->getJson('/api/v1/dashboard/analytics');

        $response->assertStatus(200);
        
        $distribution = $response->json('data.meta.class_distribution');
        
        $warriors = collect($distribution)->firstWhere('class', 'Warrior')['count'];
        $mages = collect($distribution)->firstWhere('class', 'Mage')['count'];

        $this->assertEquals(2, $warriors);
        $this->assertEquals(1, $mages);
    }
}
