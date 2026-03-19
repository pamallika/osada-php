<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\ApproveApplicationAction;
use App\Actions\Guilds\RejectApplicationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuildApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        // В будущем здесь будет проверка Policy (creator/officer)

        $applications = $guild->members()
            ->where('status', 'pending')
            ->with('user.profile')
            ->get();

        return response()->json([
            'data' => $applications->map(fn($m) => [
                'user' => $m->user,
                'profile' => $m->user->profile,
                'applied_at' => $m->joined_at,
            ])
        ]);
    }

    public function approve(Request $request, int $userId, ApproveApplicationAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $action->execute($guild, $userId);

        return response()->json(['message' => 'Application approved']);
    }

    public function reject(Request $request, int $userId, RejectApplicationAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $action->execute($guild, $userId);

        return response()->json(['message' => 'Application rejected']);
    }
}
