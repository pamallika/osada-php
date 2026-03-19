<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\UpdateMemberRoleAction;
use App\Actions\Guilds\KickMemberAction;
use App\Http\Controllers\Controller;
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
            ->with(['user.profile'])
            ->get();

        return response()->json([
            'data' => $members
        ]);
    }

    public function updateRole(Request $request, int $userId, UpdateMemberRoleAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $validated = $request->validate([
            'role' => 'required|string|in:member,officer,admin'
        ]);

        $action->execute($guild, $userId, $validated['role']);

        return response()->json(['message' => 'Member role updated']);
    }

    public function destroy(Request $request, int $userId, KickMemberAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $action->execute($guild, $user, $userId);

        return response()->json(null, 204);
    }
}
