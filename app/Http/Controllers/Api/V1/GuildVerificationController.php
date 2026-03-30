<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GuildMemberResource;
use App\Http\Resources\Api\V1\UserGearMediaResource;
use App\Http\Resources\Api\V1\UserProfileResource;
use App\Models\GuildMember;
use App\Models\User;
use App\Models\UserGearMedia;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class GuildVerificationController extends Controller
{
    use ApiResponser;

    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $newStatus = ($membership->verification_status === 'verified') ? 'updated' : 'pending';
        
        $membership->update([
            'verification_status' => $newStatus,
        ]);

        return $this->successResponse(new GuildMemberResource($membership), 'Verification request submitted');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('manageVerifications', $membership->guild);

        $members = GuildMember::where('guild_id', $membership->guild_id)
            ->with(['user.profile', 'verifier.profile'])
            ->get();

        return $this->successResponse(GuildMemberResource::collection($members));
    }

    public function show(int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('manageVerifications', $membership->guild);

        $targetMembership = GuildMember::where('guild_id', $membership->guild_id)
            ->where('user_id', $userId)
            ->with(['user.profile', 'verifier.profile'])
            ->firstOrFail();

        $targetUser = $targetMembership->user;
        $targetUser->load('gearMedia');

        return $this->successResponse([
            'membership' => new GuildMemberResource($targetMembership),
            'profile' => new UserProfileResource($targetUser->profile),
            'media' => UserGearMediaResource::collection($targetUser->gearMedia),
        ]);
    }

    public function approve(int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('manageVerifications', $membership->guild);

        $targetMembership = GuildMember::where('guild_id', $membership->guild_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        DB::transaction(function () use ($targetMembership, $user) {
            $profile = $targetMembership->user->profile;

            // Apply draft to main stats
            $profile->update([
                'attack' => $profile->draft_attack ?? $profile->attack,
                'awakening_attack' => $profile->draft_awakening_attack ?? $profile->awakening_attack,
                'defense' => $profile->draft_defense ?? $profile->defense,
                'draft_attack' => null,
                'draft_awakening_attack' => null,
                'draft_defense' => null,
            ]);

            // Handle Media
            $oldMedia = UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', false)
                ->get();

            foreach ($oldMedia as $media) {
                $path = str_replace(Storage::url(''), '', $media->url);
                Storage::disk('public')->delete($path);
                $media->delete();
            }

            UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', true)
                ->update(['is_draft' => false]);

            $targetMembership->update([
                'verification_status' => 'verified',
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);
        });

        return $this->successResponse(new GuildMemberResource($targetMembership->fresh(['verifier.profile'])), 'Verification approved');
    }

    public function reject(int $userId, Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = GuildMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        Gate::authorize('manageVerifications', $membership->guild);

        $targetMembership = GuildMember::where('guild_id', $membership->guild_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        DB::transaction(function () use ($targetMembership) {
            $profile = $targetMembership->user->profile;

            // Update main stats anyway, but status becomes incomplete
            $profile->update([
                'attack' => $profile->draft_attack ?? $profile->attack,
                'awakening_attack' => $profile->draft_awakening_attack ?? $profile->awakening_attack,
                'defense' => $profile->draft_defense ?? $profile->defense,
                'draft_attack' => null,
                'draft_awakening_attack' => null,
                'draft_defense' => null,
            ]);

            // Handle Media
            $oldMedia = UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', false)
                ->get();

            foreach ($oldMedia as $media) {
                $path = str_replace(Storage::url(''), '', $media->url);
                Storage::disk('public')->delete($path);
                $media->delete();
            }

            UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', true)
                ->update(['is_draft' => false]);

            $targetMembership->update([
                'verification_status' => 'incomplete',
                'verified_by' => null,
                'verified_at' => null,
            ]);
        });

        return $this->successResponse(new GuildMemberResource($targetMembership->fresh()), 'Verification rejected (stats updated, status incomplete)');
    }
}
