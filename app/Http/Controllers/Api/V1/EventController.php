<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EventCreateRequest;
use App\Http\Requests\Api\EventUpdateRequest;
use App\Models\Event;
use App\Models\Preset;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Actions\Events\Discord\CreateDiscordEvent;
use App\Actions\Events\Core\UpdateEventStatusAction;
use App\Actions\Events\Core\UpdateEventMessageAction;
use App\Http\Resources\Api\Discord\EventFullResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $query = Event::query()->with(['guild']);

        if ($request->has('guild_id')) {
            $query->where('guild_id', $request->guild_id);
        }

        $user = $request->user();
        if ($user) {
            $guildId = $request->guild_id;
            $member = \App\Models\GuildMember::where('user_id', $user->id)
                ->where('guild_id', $guildId)
                ->first();

            $isOfficer = $member && in_array($member->role, ['officer', 'admin', 'creator']);

            if (!$isOfficer) {
                $query->where('status', 'published');
            }
        } else {
            $query->where('status', 'published');
        }

        $events = $query->orderBy('start_at', 'desc')->get();

        return $this->successResponse(EventFullResource::collection($events));
    }

    public function store(EventCreateRequest $request, CreateDiscordEvent $action)
    {
        $guild = \App\Models\Guild::findOrFail($request->guild_id);
        $this->authorize('manageEvents', $guild);

        $event = $action->execute($request->validated());
        return $this->successResponse(new EventFullResource($event->load(['squads', 'guild'])), 'Event created');
    }

    public function update($id, EventUpdateRequest $request)
    {
        $event = Event::query()->findOrFail($id);
        $this->authorize('update', $event);

        $data = $request->validated();

        if (isset($data['start_at'])) {
            $data['start_at'] = Carbon::parse($data['start_at'])->setTimezone('UTC');
        }

        $event->update($data);

        return $this->successResponse(new EventFullResource($event->load(['squads', 'guild'])), 'Event updated');
    }

    public function show($id)
    {
        $event = Event::query()->with(['squads.participants.user.profile', 'participants.user.profile', 'guild'])
            ->findOrFail($id);
        return $this->successResponse(new EventFullResource($event));
    }

    public function applyPreset(Request $request, $id)
    {
        $request->validate(['preset_id' => 'required|exists:presets,id']);

        $event = Event::query()->findOrFail($id);
        $this->authorize('update', $event);
        
        $preset = Preset::query()->findOrFail($request->preset_id);

        DB::transaction(function () use ($event, $preset) {
            $event->squads()->delete();

            $totalSlots = 0;
            foreach ($preset->structure as $index => $squadTemplate) {
                $event->squads()->create([
                    'title' => $squadTemplate['name'],
                    'slots_limit' => $squadTemplate['slots'],
                    'position' => $index,
                ]);
                $totalSlots += $squadTemplate['slots'];
            }

            $event->total_slots = $totalSlots;
            $event->save();
        });

        return $this->show($event->id);
    }

    public function publish(Request $request, $id, UpdateEventStatusAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $this->authorize('update', $event);
        
        $notificationSettings = null;
        if ($request->has('platforms') || $request->has('roles')) {
            $notificationSettings = [
                'platforms' => $request->input('platforms', []),
                'roles' => $request->input('roles', []),
            ];
        }

        $action->execute($event, 'published', $notificationSettings);
        return $this->show($id);
    }

    public function cancel($id, UpdateEventStatusAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $this->authorize('update', $event);
        
        $action->execute($event, 'cancelled');

        return $this->successResponse(new EventFullResource($event), 'Event cancelled');
    }

    public function archive($id, UpdateEventStatusAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $this->authorize('update', $event);
        
        $action->execute($event, 'archived');

        return $this->successResponse(new EventFullResource($event), 'Event archived');
    }

    public function updateMessageId(Request $request, $id, UpdateEventMessageAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $this->authorize('update', $event);
        
        $action->execute($event, $request->discord_message_id);

        return $this->successResponse(new EventFullResource($event), 'Message ID updated');
    }
}
