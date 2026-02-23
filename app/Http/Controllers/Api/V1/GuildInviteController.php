<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Guilds\AcceptInviteAction;
use App\Http\Controllers\Controller;
use App\Actions\Guilds\GenerateInviteAction;
use App\Models\Guild;
use App\Models\GuildInvite;
use Illuminate\Http\Request;

class GuildInviteController extends Controller
{
    public function store(Request $request, Guild $guild, GenerateInviteAction $action)
    {
        // TODO: Добавить проверку Gate/Policy, что $request->user() является лидером или офицером этой гильдии

        $validated = $request->validate([
            'max_uses' => 'nullable|integer|min:1',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $invite = $action->execute($guild, $request->user(), $validated);

        return response()->json([
            'message' => 'Invite generated',
            'invite_url' => config('app.url') . '/invite/' . $invite->token,
            'data' => $invite
        ], 201);
    }

    public function show(string $token)
    {
        $invite = GuildInvite::with(['guild:id,name,logo_url', 'creator:id,name'])
            ->where('token', $token)
            ->firstOrFail();

        if (!$invite->isValid()) {
            return response()->json(['message' => 'Invite is no longer valid'], 400);
        }

        return response()->json([
            'data' => [
                'guild' => $invite->guild,
                'invited_by' => $invite->creator,
            ]
        ]);
    }

    public function accept(Request $request, string $token, AcceptInviteAction $action)
    {
        $action->execute($request->user(), $token);

        return response()->json([
            'message' => 'Successfully joined the guild!'
        ]);
    }
}
