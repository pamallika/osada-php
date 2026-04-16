<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSquad;
use App\Models\Preset;
use App\Actions\Events\Core\ApplyPresetToSquadAction;
use App\Http\Resources\Api\Discord\EventFullResource;
use App\Events\SquadUpdated;
use App\Events\ParticipantUpdated;
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
        $this->authorize('update', $squad->event);
        
        $preset = Preset::query()->findOrFail($request->preset_id);

        $addedCount = $action->execute($squad, $preset);

        // Broadcast that the squad was updated (new participants)
        broadcast(new SquadUpdated($squad->event_id, 'updated', [
            'id' => $squad->id,
            'title' => $squad->title,
            'slots_limit' => $squad->slots_limit,
        ]));

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
        $this->authorize('update', $event);

        if ($event->status === 'archived') {
            return $this->errorResponse('Cannot add squads to an archived event', 403);
        }

        $squad = $event->squads()->create([
            'title' => $request->name,
            'slots_limit' => $request->limit,
            'position' => $event->squads()->count(),
        ]);

        $event->total_slots = $event->squads()->sum('slots_limit');
        $event->save();

        broadcast(new SquadUpdated($event->id, 'created', [
            'id' => $squad->id,
            'title' => $squad->title,
            'slots_limit' => $squad->slots_limit,
        ]));

        \App\Jobs\UpdateMessengerEventMessage::dispatch($event->id)->delay(now()->addSeconds(5));

        return $this->successResponse(
            new EventFullResource($event->load(['squads.participants.user.profile', 'participants.user.profile', 'guild'])),
            'Squad created successfully'
        );
    }

    public function update(Request $request, $eventId, $squadId)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'limit' => 'sometimes|required|integer|min:0',
        ]);

        $event = Event::query()->findOrFail($eventId);
        $this->authorize('update', $event);

        if ($event->status === 'archived') {
            return $this->errorResponse('Cannot modify squads in an archived event', 403);
        }

        $squad = $event->squads()->findOrFail($squadId);

        if ($squad->is_system) {
            return $this->errorResponse('System squads cannot be modified', 403);
        }

        if ($request->has('limit')) {
            $newLimit = $request->limit;
            $currentParticipantsCount = $squad->participants()->count();

            if ($newLimit < $currentParticipantsCount) {
                return $this->errorResponse("Cannot reduce limit below current participants count ({$currentParticipantsCount}). Move extra participants first.", 422);
            }
            $squad->slots_limit = $newLimit;
        }

        if ($request->has('name')) {
            $squad->title = $request->name;
        }

        $squad->save();

        $event->total_slots = $event->squads()->sum('slots_limit');
        $event->save();

        broadcast(new SquadUpdated($event->id, 'updated', [
            'id' => $squad->id,
            'title' => $squad->title,
            'slots_limit' => $squad->slots_limit,
        ]));

        \App\Jobs\UpdateMessengerEventMessage::dispatch($event->id)->delay(now()->addSeconds(5));

        return $this->successResponse(
            new EventFullResource($event->load(['squads.participants.user.profile', 'participants.user.profile', 'guild'])),
            'Squad updated successfully'
        );
    }

    public function destroy($eventId, $squadId)
    {
        $event = Event::query()->findOrFail($eventId);
        $this->authorize('update', $event);

        if ($event->status === 'archived') {
            return $this->errorResponse('Cannot delete squads from an archived event', 403);
        }

        return DB::transaction(function () use ($event, $squadId) {
            $squad = $event->squads()->findOrFail($squadId);

            if ($squad->is_system) {
                return $this->errorResponse('System squads cannot be deleted', 403);
            }

            // Находим системный "Резерв" для этого события
            $reserveSquad = $event->squads()->where('is_system', true)->first();

            $participantIds = $squad->participants()->pluck('user_id')->toArray();

            if ($reserveSquad) {
                // Перемещаем всех участников в Резерв
                $squad->participants()->update(['squad_id' => $reserveSquad->id]);
            } else {
                // Если вдруг Резерва нет (не должно случаться), просто отвязываем от отряда (будут в общем списке участников)
                $squad->participants()->update(['squad_id' => null]);
            }

            $squadIdForBroadcast = $squad->id;
            $squadTitle = $squad->title;
            $squadLimit = $squad->slots_limit;

            $squad->delete();

            $event->total_slots = $event->squads()->sum('slots_limit');
            $event->save();

            broadcast(new SquadUpdated($event->id, 'deleted', [
                'id' => $squadIdForBroadcast,
                'title' => $squadTitle,
                'slots_limit' => $squadLimit,
            ]));

            // Also broadcast participant moves if any
            foreach ($participantIds as $userId) {
                broadcast(new ParticipantUpdated($event->id, 'moved', [
                    'user_id' => $userId,
                    'squad_id' => $reserveSquad ? $reserveSquad->id : null,
                    'status' => 'confirmed'
                ]));
            }

            \App\Jobs\UpdateMessengerEventMessage::dispatch($event->id)->delay(now()->addSeconds(5));

            return $this->successResponse(
                new EventFullResource($event->load(['squads.participants.user.profile', 'participants.user.profile', 'guild'])),
                'Squad deleted successfully'
            );
        });
    }

    public function reorder(Request $request, $eventId)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:event_squads,id'
        ]);

        $event = Event::query()->findOrFail($eventId);
        $this->authorize('update', $event);

        if ($event->status === 'archived') {
            return $this->errorResponse('Cannot reorder squads in an archived event', 403);
        }

        DB::transaction(function () use ($event, $request) {
            foreach ($request->ids as $index => $id) {
                // We only update non-system squads that belong to this event
                $event->squads()
                    ->where('id', $id)
                    ->where('is_system', false)
                    ->update(['position' => $index]);
            }
        });

        return $this->successResponse(
            new EventFullResource($event->load(['squads.participants.user.profile', 'participants.user.profile', 'guild'])),
            'Squads reordered successfully'
        );
    }
}

