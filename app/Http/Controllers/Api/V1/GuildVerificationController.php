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
use App\Actions\Guilds\SubmitVerificationAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GuildVerificationController extends Controller
{
    use ApiResponser;

    public function submit(Request $request, SubmitVerificationAction $action): JsonResponse
    {
        $membership = $action->execute($request->user());

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
            ->with(['user', 'user.profile', 'user.linked_accounts', 'verifier.profile'])
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
            ->with(['user', 'user.profile', 'user.linked_accounts', 'verifier.profile'])
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

        // Check if all 4 mandatory labels are present
        $mandatoryLabels = ['crystal', 'relic', 'zakalk', 'gear'];
        $existingLabels = $targetMembership->user->gearMedia()
            ->whereIn('label', $mandatoryLabels)
            ->pluck('label')
            ->unique()
            ->toArray();

        if (count(array_intersect($mandatoryLabels, $existingLabels)) < 4) {
             throw ValidationException::withMessages(['gear' => 'Профиль не заполнен']);
        }

        DB::transaction(function () use ($targetMembership, $user) {
            $profile = $targetMembership->user->profile;

            // Handle Media: Identify labels being updated by drafts
            $drafts = UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', true)
                ->get();

            $draftLabels = $drafts->pluck('label')->toArray();

            // 1. Delete OLD verified media that is being REPLACED by these drafts
            $oldMedia = UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', false)
                ->whereIn('label', $draftLabels)
                ->get();

            foreach ($oldMedia as $media) {
                $path = str_replace(Storage::url(''), '', $media->url);
                Storage::disk('public')->delete($path);
                $media->delete();
            }

            // 2. Promote drafts to verified status
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

            $profile = $targetMembership->user->profile;

            // Handle Media: Delete ONLY drafts. Keep the previously verified set.
            $draftMedia = UserGearMedia::where('user_id', $targetMembership->user_id)
                ->where('is_draft', true)
                ->get();

            foreach ($draftMedia as $media) {
                $path = str_replace(Storage::url(''), '', $media->url);
                Storage::disk('public')->delete($path);
                $media->delete();
            }

            $targetMembership->update([
                'verification_status' => 'incomplete',
                'verified_by' => null,
                'verified_at' => null,
            ]);
        });

        return $this->successResponse(new GuildMemberResource($targetMembership->fresh()), 'Verification rejected (stats updated, status incomplete)');
    }
}
