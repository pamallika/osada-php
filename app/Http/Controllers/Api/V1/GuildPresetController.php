<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreatePresetRequest;
use App\Models\Preset;
use App\Models\GuildIntegration;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class GuildPresetController extends Controller
{
    use ApiResponser;

    public function store(CreatePresetRequest $request)
    {
        $guildId = GuildIntegration::where('provider', 'discord')
            ->where('platform_id', $request->discord_id)
            ->value('guild_id');

        if (!$guildId) {
            return $this->errorResponse('Guild has no active connection', 404);
        }

        $preset = Preset::query()->create([
            'guild_id' => $guildId,
            'title' => $request->name,
            'structure' => $request->structure,
        ]);

        return $this->successResponse($preset, 'Preset created successfully');
    }

    public function index($discord_id)
    {
        $guildId = GuildIntegration::where('provider', 'discord')
            ->where('platform_id', $discord_id)
            ->value('guild_id');

        if (!$guildId) {
            return $this->errorResponse('Guild not found', 404);
        }

        $presets = Preset::where('guild_id', $guildId)
            ->orderBy('title', 'asc')
            ->get();

        return $this->successResponse($presets);
    }

    public function destroy($id)
    {
        Preset::destroy($id);
        return $this->successResponse([]);
    }
}
