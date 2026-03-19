<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Guild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_change_roles()
    {
        $creator = User::factory()->create();
        $creator->profile()->create(['family_name' => 'Creator']);
        $guild = Guild::factory()->create(['owner_id' => $creator->id]);
        $guild->members()->create(['user_id' => $creator->id, 'role' => 'creator', 'status' => 'active']);

        $member = User::factory()->create();
        $member->profile()->create(['family_name' => 'Member']);
        $guild->members()->create(['user_id' => $member->id, 'role' => 'member', 'status' => 'active']);

        $token = $creator->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/guilds/my/members/{$member->id}/role", [
                'role' => 'officer'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('guild_members', [
            'user_id' => $member->id,
            'role' => 'officer'
        ]);
    }

    public function test_officer_cannot_change_roles()
    {
        $officer = User::factory()->create();
        $officer->profile()->create(['family_name' => 'Officer']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $officer->id, 'role' => 'officer', 'status' => 'active']);

        $member = User::factory()->create();
        $member->profile()->create(['family_name' => 'Member']);
        $guild->members()->create(['user_id' => $member->id, 'role' => 'member', 'status' => 'active']);

        $token = $officer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/guilds/my/members/{$member->id}/role", [
                'role' => 'admin'
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'INSUFFICIENT_PERMISSIONS']);
    }

    public function test_admin_can_see_applications()
    {
        $admin = User::factory()->create();
        $admin->profile()->create(['family_name' => 'Admin']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/guilds/my/applications');

        $response->assertStatus(200);
    }

    public function test_officer_cannot_see_applications()
    {
        $officer = User::factory()->create();
        $officer->profile()->create(['family_name' => 'Officer']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $officer->id, 'role' => 'officer', 'status' => 'active']);

        $token = $officer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/guilds/my/applications');

        $response->assertStatus(403)
            ->assertJson(['error' => 'INSUFFICIENT_PERMISSIONS']);
    }
}
