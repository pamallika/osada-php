<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\ApproveApplicationAction;
use App\Actions\Guilds\RejectApplicationAction;
use App\Http\Controllers\Controller;
use App\Events\GuildApplicationProcessed;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use App\Http\Resources\Api\V1\GuildMemberResource;
use App\Traits\ApiResponser;

class GuildApplicationController extends Controller
{
    use ApiResponser;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        // В будущем здесь будет проверка Policy (creator/officer)

        $applications = $guild->members()
            ->where('status', 'pending')
            ->with(['user.profile', 'user.linked_accounts'])
            ->get();

        return $this->successResponse(GuildMemberResource::collection($applications));
    }

    public function approve(Request $request, int $userId, ApproveApplicationAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $action->execute($guild, $userId);

        broadcast(new GuildApplicationProcessed($userId, 'approved', [
            'id' => $guild->id,
            'name' => $guild->name,
            'logo_url' => $guild->logo_url,
        ]));

        return $this->successResponse(null, 'Application approved');
    }

    public function reject(Request $request, int $userId, RejectApplicationAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $action->execute($guild, $userId);

        broadcast(new GuildApplicationProcessed($userId, 'rejected', [
            'id' => $guild->id,
            'name' => $guild->name,
            'logo_url' => $guild->logo_url,
        ]));

        return $this->successResponse(null, 'Application rejected');
    }
}
