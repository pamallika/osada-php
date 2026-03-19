<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Guild;
use App\Models\GuildInvite;
use App\Models\GuildMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_apply_via_invite()
    {
        $guild = Guild::factory()->create();
        $invite = GuildInvite::create([
            'guild_id' => $guild->id,
            'token' => 'testtoken',
            'created_by' => $guild->owner_id,
        ]);

        $applicant = User::factory()->create();
        $applicant->profile()->create(['family_name' => 'Applicant']);
        $token = $applicant->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/invites/{$invite->token}/accept");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Заявка подана']);

        $this->assertDatabaseHas('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $applicant->id,
            'status' => 'pending',
        ]);
    }

    public function test_leader_can_see_and_approve_application()
    {
        $leader = User::factory()->create();
        $leader->profile()->create(['family_name' => 'Leader']);
        $guild = Guild::factory()->create(['owner_id' => $leader->id]);
        $guild->members()->create(['user_id' => $leader->id, 'role' => 'creator', 'status' => 'active']);

        $applicant = User::factory()->create();
        $applicant->profile()->create(['family_name' => 'Applicant']);
        $guild->members()->create(['user_id' => $applicant->id, 'role' => 'member', 'status' => 'pending']);

        $token = $leader->createToken('test')->plainTextToken;

        // Check applications list
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/guilds/my/applications');

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Approve
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/guilds/my/applications/{$applicant->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $applicant->id,
            'status' => 'active',
        ]);
    }

    public function test_leader_can_reject_application()
    {
        $leader = User::factory()->create();
        $leader->profile()->create(['family_name' => 'Leader']);
        $guild = Guild::factory()->create(['owner_id' => $leader->id]);
        $guild->members()->create(['user_id' => $leader->id, 'role' => 'creator', 'status' => 'active']);

        $applicant = User::factory()->create();
        $applicant->profile()->create(['family_name' => 'Applicant']);
        $guild->members()->create(['user_id' => $applicant->id, 'role' => 'member', 'status' => 'pending']);

        $token = $leader->createToken('test')->plainTextToken;

        // Reject
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/guilds/my/applications/{$applicant->id}/reject");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $applicant->id,
        ]);
    }

    public function test_leader_can_get_guild_invite_link()
    {
        $leader = User::factory()->create();
        $leader->profile()->create(['family_name' => 'Leader']);
        $guild = Guild::factory()->create(['owner_id' => $leader->id]);
        $guild->members()->create(['user_id' => $leader->id, 'role' => 'creator', 'status' => 'active']);

        $token = $leader->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/guilds/my/invite');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'url'
                ]
            ])
            ->assertJson(['status' => 'success']);
    }
}
