<?php

namespace Tests\Feature\Api\V1;

use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuildInviteSlugTest extends TestCase
{
    use RefreshDatabase;

    protected $creator;
    protected $admin;
    protected $officer;
    protected $member;
    protected $guild;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Guild
        $this->guild = Guild::factory()->create(['name' => 'Test Guild', 'invite_slug' => 'test-slug']);

        // Create Creator
        $this->creator = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->creator->id, 'family_name' => 'CreatorFamily']);
        GuildMember::create([
            'guild_id' => $this->guild->id,
            'user_id' => $this->creator->id,
            'role' => 'creator',
            'status' => 'active',
        ]);

        // Create Admin
        $this->admin = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->admin->id, 'family_name' => 'AdminFamily']);
        GuildMember::create([
            'guild_id' => $this->guild->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create Officer
        $this->officer = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->officer->id, 'family_name' => 'OfficerFamily']);
        GuildMember::create([
            'guild_id' => $this->guild->id,
            'user_id' => $this->officer->id,
            'role' => 'officer',
            'status' => 'active',
        ]);

        // Create Member
        $this->member = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->member->id, 'family_name' => 'MemberFamily']);
        GuildMember::create([
            'guild_id' => $this->guild->id,
            'user_id' => $this->member->id,
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    public function test_creator_can_update_invite_slug()
    {
        $response = $this->actingAs($this->creator)
            ->patchJson('/api/v1/guilds/my/invite-slug', [
                'invite_slug' => 'new-cool-slug',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.invite_slug', 'new-cool-slug');

        $this->assertDatabaseHas('guilds', [
            'id' => $this->guild->id,
            'invite_slug' => 'new-cool-slug',
        ]);
    }

    public function test_creator_can_save_same_invite_slug_without_error()
    {
        $response = $this->actingAs($this->creator)
            ->patchJson('/api/v1/guilds/my/invite-slug', [
                'invite_slug' => 'test-slug',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.invite_slug', 'test-slug');
    }

    public function test_admin_cannot_update_invite_slug()
    {
        $response = $this->actingAs($this->admin)
            ->patchJson('/api/v1/guilds/my/invite-slug', [
                'invite_slug' => 'admin-slug',
            ]);

        $response->assertStatus(403);
    }

    public function test_officer_cannot_update_invite_slug()
    {
        $response = $this->actingAs($this->officer)
            ->patchJson('/api/v1/guilds/my/invite-slug', [
                'invite_slug' => 'officer-slug',
            ]);

        $response->assertStatus(403);
    }

    public function test_member_cannot_update_invite_slug()
    {
        $response = $this->actingAs($this->member)
            ->patchJson('/api/v1/guilds/my/invite-slug', [
                'invite_slug' => 'member-slug',
            ]);

        $response->assertStatus(403);
    }

    public function test_invite_slug_must_be_unique()
    {
        Guild::factory()->create(['invite_slug' => 'taken-slug']);

        $response = $this->actingAs($this->creator)
            ->patchJson('/api/v1/guilds/my/invite-slug', [
                'invite_slug' => 'taken-slug',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invite_slug']);
    }
}
