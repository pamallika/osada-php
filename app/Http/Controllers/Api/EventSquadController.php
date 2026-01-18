<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventSquad;
use App\Models\Preset;
use App\Actions\Events\Core\ApplyPresetToSquadAction;
use App\Http\Resources\Api\Discord\EventFullResource;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class EventSquadController extends Controller
{
    use ApiResponser;

    public function store(Request $request, $squadId, ApplyPresetToSquadAction $action)
    {
        $request->validate(['preset_id' => 'required|exists:presets,id']);

        $squad = EventSquad::query()->findOrFail($squadId);
        $preset = Preset::query()->findOrFail($request->preset_id);

        $addedCount = $action->execute($squad, $preset);

        return $this->successResponse(
            new EventFullResource($squad->event->load(['squads.participants.user.profile', 'participants.user.profile'])),
            "Added {$addedCount} users from preset"
        );
    }
}
