<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\UpdateMemberRoleAction;
use App\Actions\Guilds\KickMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GuildMemberResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuildMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $members = $guild->members()
            ->where('status', 'active')
            ->with(['guild', 'user.profile', 'user.linked_accounts'])
            ->get();


        return GuildMemberResource::collection($members)->response();
    }

    public function updateRole(Request $request, int $userId, UpdateMemberRoleAction $action): JsonResponse
    {
        $targetUser = User::findOrFail($userId);
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $this->authorize('manageMembers', [$guild, $targetUser]);

        $validated = $request->validate([
            'role' => 'required|string|in:member,officer,admin'
        ]);

        \Illuminate\Support\Facades\Gate::authorize('assignRole', [$guild, $validated['role']]);

        if ($user->id === $targetUser->id) {


             return response()->json(['message' => 'You cannot change your own role.'], 403);
        }

        $action->execute($guild, $targetUser, $validated['role']);

        return response()->json(['message' => 'Member role updated']);
    }

    public function destroy(Request $request, int $userId, KickMemberAction $action): JsonResponse
    {
        $targetUser = User::findOrFail($userId);
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $this->authorize('manageMembers', [$guild, $targetUser]);

        if ($user->id === $targetUser->id) {
             return response()->json(['message' => 'You cannot kick yourself.'], 403);
        }

        $action->execute($guild, $targetUser);

        return response()->json(null, 204);
    }
}

