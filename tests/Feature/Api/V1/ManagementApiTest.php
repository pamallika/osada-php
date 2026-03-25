<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Guild;
use App\Models\GuildMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_kick_member()
    {
        $adminUser = User::factory()->create();
        $adminUser->profile()->create(['family_name' => 'Admin']);
        $guild = Guild::factory()->create(['owner_id' => $adminUser->id]);
        $guild->members()->create(['user_id' => $adminUser->id, 'role' => 'admin', 'status' => 'active']);

        $memberToKick = User::factory()->create();
        $memberToKick->profile()->create(['family_name' => 'ToKick']);
        $guild->members()->create(['user_id' => $memberToKick->id, 'role' => 'member', 'status' => 'active']);

        $token = $adminUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/guilds/my/members/{$memberToKick->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $memberToKick->id
        ]);
    }

    public function test_admin_can_kick_another_admin()
    {
        $admin1 = User::factory()->create();
        $admin1->profile()->create(['family_name' => 'Admin1']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $admin1->id, 'role' => 'admin', 'status' => 'active']);

        $admin2 = User::factory()->create();
        $admin2->profile()->create(['family_name' => 'Admin2']);
        $guild->members()->create(['user_id' => $admin2->id, 'role' => 'admin', 'status' => 'active']);

        $token = $admin1->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/guilds/my/members/{$admin2->id}");

        $response->assertStatus(204);
    }

    public function test_admin_cannot_kick_creator()
    {
        $admin = User::factory()->create();
        $admin->profile()->create(['family_name' => 'Admin']);
        $creator = User::factory()->create();
        $creator->profile()->create(['family_name' => 'Creator']);
        $guild = Guild::factory()->create(['owner_id' => $creator->id]);
        $guild->members()->create(['user_id' => $creator->id, 'role' => 'creator', 'status' => 'active']);
        $guild->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/guilds/my/members/{$creator->id}");

        $response->assertStatus(403);
    }


    public function test_creator_can_kick_admin()
    {
        $creator = User::factory()->create();
        $creator->profile()->create(['family_name' => 'Creator']);
        $guild = Guild::factory()->create(['owner_id' => $creator->id]);
        $guild->members()->create(['user_id' => $creator->id, 'role' => 'creator', 'status' => 'active']);

        $admin = User::factory()->create();
        $admin->profile()->create(['family_name' => 'Admin']);
        $guild->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $token = $creator->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/guilds/my/members/{$admin->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $admin->id
        ]);
    }

    public function test_member_cannot_kick_anyone()
    {
        $member1 = User::factory()->create();
        $member1->profile()->create(['family_name' => 'Member1']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $member1->id, 'role' => 'member', 'status' => 'active']);

        $member2 = User::factory()->create();
        $member2->profile()->create(['family_name' => 'Member2']);
        $guild->members()->create(['user_id' => $member2->id, 'role' => 'member', 'status' => 'active']);

        $token = $member1->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/guilds/my/members/{$member2->id}");

        // Protected by middleware role:admin
        $response->assertStatus(403);
    }

    public function test_member_gets_403_if_no_invite_exists()
    {
        $member = User::factory()->create();
        $member->profile()->create(['family_name' => 'Member']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $member->id, 'role' => 'member', 'status' => 'active']);

        $token = $member->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/guilds/my/invite");

        $response->assertStatus(403)
            ->assertJson(['error' => 'INSUFFICIENT_PERMISSIONS']);
    }

    public function test_admin_cannot_assign_admin_role()

    {
        $admin = User::factory()->create();
        $admin->profile()->create(['family_name' => 'Admin']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $target = User::factory()->create();
        $target->profile()->create(['family_name' => 'ToUpdate']);
        $guild->members()->create(['user_id' => $target->id, 'role' => 'member', 'status' => 'active']);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/guilds/my/members/{$target->id}/role", [
                'role' => 'admin'
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_assign_officer_role()
    {
        $admin = User::factory()->create();
        $admin->profile()->create(['family_name' => 'Admin']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $target = User::factory()->create();
        $target->profile()->create(['family_name' => 'ToUpdate']);
        $guild->members()->create(['user_id' => $target->id, 'role' => 'member', 'status' => 'active']);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/guilds/my/members/{$target->id}/role", [
                'role' => 'officer'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $target->id,
            'role' => 'officer'
        ]);
    }

    public function test_admin_can_downgrade_another_admin()
    {
        $admin = User::factory()->create();
        $admin->profile()->create(['family_name' => 'Admin']);
        $guild = Guild::factory()->create();
        $guild->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'status' => 'active']);

        $target = User::factory()->create();
        $target->profile()->create(['family_name' => 'TargetAdmin']);
        $guild->members()->create(['user_id' => $target->id, 'role' => 'admin', 'status' => 'active']);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/guilds/my/members/{$target->id}/role", [
                'role' => 'member'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('guild_members', [
            'guild_id' => $guild->id,
            'user_id' => $target->id,
            'role' => 'member'
        ]);
    }
}

