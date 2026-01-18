<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSquad;
use App\Models\Preset;
use App\Actions\Events\Core\ApplyPresetToSquadAction;
use App\Http\Resources\Api\Discord\EventFullResource;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventSquadController extends Controller
{
    use ApiResponser;

    public function applyPreset(Request $request, $squadId, ApplyPresetToSquadAction $action)
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

    public function store(Request $request, $eventId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'limit' => 'required|integer|min:1',
        ]);

        $event = Event::query()->findOrFail($eventId);

        $event->squads()->create([
            'title' => $request->name,
            'slots_limit' => $request->limit,
            'position' => $event->squads()->count(),
        ]);

        $event->total_slots = $event->squads()->sum('slots_limit');
        $event->save();

        return $this->successResponse(null, 'Squad created successfully');
    }

    public function destroy($eventId, $squadId)
    {
        return DB::transaction(function () use ($eventId, $squadId) {
            $event = Event::query()->findOrFail($eventId);
            $squad = $event->squads()->findOrFail($squadId);

            $squad->delete();

            $event->total_slots = $event->squads()->sum('slots_limit');
            $event->save();

            return $this->successResponse(null, 'Squad deleted successfully');
        });
    }
}
