<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GuildSetupRequest;
use App\Actions\Discord\SetupGuildAction;
use App\Models\Guild;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class GuildController extends Controller
{
    use ApiResponser;

    public function store(GuildSetupRequest $request, SetupGuildAction $action)
    {
        $guild = $action->execute($request->validated());
        return $this->successResponse($guild, 'Guild settings updated');
    }

    public function checkAccess(Request $request, $discord_guild_id)
    {
        $guild = Guild::query()->where('discord_id', $discord_guild_id)->firstOrFail();
        $userRoles = $request->input('roles', []);

        $hasAccess = !empty(array_intersect($userRoles, $guild->officer_role_ids ?? []));

        return $this->successResponse($hasAccess);
    }

    public function presets($guildId)
    {
        $guild = Guild::query()->where('discord_id', $guildId)->with('presets')->firstOrFail();
        return $this->successResponse($guild->presets);
    }

    public function show($discord_id)
    {
        $guild = Guild::query()->where('discord_id', $discord_id)->first();

        return $this->successResponse($guild ?? []);
    }
}
