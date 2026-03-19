<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\AcceptInviteAction;
use App\Http\Controllers\Controller;
use App\Actions\Guilds\GenerateInviteAction;
use App\Models\Guild;
use App\Models\GuildInvite;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GuildInviteController extends Controller
{
    use ApiResponser;

    public function store(Request $request, Guild $guild, GenerateInviteAction $action)
    {
        $this->authorize('createInvite', $guild);

        $validated = $request->validate([
            'max_uses' => 'nullable|integer|min:1',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $invite = $action->execute($guild, $request->user(), $validated);

        return $this->successResponse([
            'invite_url' => config('app.url') . '/invite/' . $invite->token,
            'invite' => $invite
        ], 'Invite generated', 201);
    }

    public function show(string $token)
    {
        $invite = GuildInvite::with(['guild:id,name,logo_url', 'creator:id,name'])
            ->where('token', $token)
            ->firstOrFail();

        if (!$invite->isValid()) {
            return $this->errorResponse('Invite is no longer valid', 400);
        }

        return $this->successResponse([
            'guild' => $invite->guild,
            'invited_by' => $invite->creator,
        ]);
    }

    public function accept(Request $request, string $token, AcceptInviteAction $action)
    {
        $action->execute($request->user(), $token);

        return $this->successResponse(null, 'Заявка подана');
    }

    public function myInvite(Request $request, GenerateInviteAction $action): JsonResponse
    {
        $user = $request->user();
        $membership = $user->guildMemberships()->where('status', 'active')->firstOrFail();
        $guild = $membership->guild;

        $invite = $guild->invites()->get()->first(fn($i) => $i->isValid());

        if (!$invite) {
            $roles = [
                'member' => 1,
                'officer' => 2,
                'admin' => 3,
                'creator' => 4,
            ];

            $currentRoleWeight = $roles[$membership->role] ?? 0;

            if ($currentRoleWeight < 2) { // Less than officer
                return $this->errorResponse('No active invite found. Please ask an officer to generate one.', 403, 'INSUFFICIENT_PERMISSIONS');
            }

            $invite = $action->execute($guild, $user, []);
        }

        return $this->successResponse([
            'url' => config('app.url') . '/invite/' . $invite->token
        ]);
    }
}
