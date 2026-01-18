<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreatePresetRequest;
use App\Models\GuildPreset;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class GuildPresetController extends Controller
{
    use ApiResponser;

    public function store(CreatePresetRequest $request)
    {
        $preset = GuildPreset::query()->create([
            'guild_id' => $request->discord_id,
            'name' => $request->name,
            'structure' => $request->structure,
        ]);

        return $this->successResponse($preset, 'Preset created successfully');
    }

    public function index($guild_id)
    {
        $presets = GuildPreset::where('guild_id', $guild_id)
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($presets);
    }

    public function destroy($id)
    {
        GuildPreset::destroy($id);
        return $this->successResponse([]);
    }
}
