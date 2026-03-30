<?php

namespace Tests\Feature\Api\V1;

use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use App\Models\UserGearMedia;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GearVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $officer;
    protected $guild;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $this->user->id,
            'family_name' => 'UserFamily',
            'char_class' => 'Warrior',
            'attack' => 280,
            'awakening_attack' => 282,
            'defense' => 340,
        ]);

        $this->officer = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $this->officer->id, 'family_name' => 'OfficerFamily']);

        $this->guild = Guild::factory()->create(['name' => 'Test Guild']);

        GuildMember::create([
            'guild_id' => $this->guild->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'status' => 'active',
            'verification_status' => 'incomplete',
        ]);

        GuildMember::create([
            'guild_id' => $this->guild->id,
            'user_id' => $this->officer->id,
            'role' => 'officer',
            'status' => 'active',
        ]);
    }

    public function test_user_can_upload_gear_media()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/auth/gear/media', [
                'file' => UploadedFile::fake()->image('gear.jpg'),
                'label' => 'Main Build',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_gear_media', [
            'user_id' => $this->user->id,
            'label' => 'Main Build',
            'is_draft' => true,
        ]);
    }

    public function test_user_can_submit_verification_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/guilds/my/verification/submit');

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'pending');

        $this->assertDatabaseHas('guild_members', [
            'user_id' => $this->user->id,
            'verification_status' => 'pending',
        ]);
    }

    public function test_officer_can_approve_verification()
    {
        // Setup draft stats
        $this->user->profile->update([
            'draft_attack' => 290,
            'draft_awakening_attack' => 292,
            'draft_defense' => 350,
        ]);

        // Setup draft media
        UserGearMedia::create([
            'user_id' => $this->user->id,
            'url' => 'http://example.com/draft.webp',
            'is_draft' => true,
            'size' => 1000,
        ]);

        // Setup current media (should be deleted)
        UserGearMedia::create([
            'user_id' => $this->user->id,
            'url' => 'http://example.com/old.webp',
            'is_draft' => false,
            'size' => 1000,
        ]);

        $response = $this->actingAs($this->officer)
            ->postJson("/api/v1/guilds/my/verifications/{$this->user->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'verified');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'attack' => 290,
            'draft_attack' => null,
        ]);

        $this->assertDatabaseHas('user_gear_media', [
            'user_id' => $this->user->id,
            'is_draft' => false,
        ]);

        $this->assertDatabaseCount('user_gear_media', 1);
        
        $this->assertDatabaseHas('guild_members', [
            'user_id' => $this->user->id,
            'verification_status' => 'verified',
            'verified_by' => $this->officer->id,
        ]);
    }

    public function test_officer_can_reject_verification()
    {
        $this->user->profile->update([
            'draft_attack' => 295,
        ]);

        $response = $this->actingAs($this->officer)
            ->postJson("/api/v1/guilds/my/verifications/{$this->user->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'incomplete');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'attack' => 295,
            'draft_attack' => null,
        ]);
        
        $this->assertDatabaseHas('guild_members', [
            'user_id' => $this->user->id,
            'verification_status' => 'incomplete',
        ]);
    }

    public function test_member_cannot_approve_verification()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/guilds/my/verifications/{$this->user->id}/approve");

        $response->assertStatus(403);
    }
}
