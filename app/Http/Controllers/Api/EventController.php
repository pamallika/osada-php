<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EventCreateRequest;
use App\Models\Event;
use App\Models\GuildPreset;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Actions\Events\Discord\CreateDiscordEvent;
use App\Actions\Events\Core\UpdateEventStatusAction;
use App\Actions\Events\Core\UpdateEventMessageAction;
use App\Http\Resources\Api\Discord\EventFullResource;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    use ApiResponser;

    public function store(EventCreateRequest $request, CreateDiscordEvent $action)
    {
        $event = $action->execute($request->validated());
        return $this->successResponse(new EventFullResource($event->load(['squads', 'guild'])), 'Event created');
    }

    public function show($id)
    {
        $event = Event::query()->with(['squads.participants.user.profile', 'participants.user.profile', 'guild'])
            ->findOrFail($id);
        return $this->successResponse(new EventFullResource($event));
    }

    public function applyPreset(Request $request, $id)
    {
        $request->validate(['preset_id' => 'required|exists:guild_presets,id']);

        $event = Event::query()->findOrFail($id);
        $preset = GuildPreset::query()->findOrFail($request->preset_id);

        DB::transaction(function () use ($event, $preset) {
            $event->squads()->delete();

            $totalSlots = 0;
            foreach ($preset->structure as $index => $squadTemplate) {
                // ИСПРАВЛЕНО: При создании отряда используем имя колонки 'title'
                $event->squads()->create([
                    'title' => $squadTemplate['name'], // Читаем 'name' из пресета, записываем в 'title'
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

    public function publish($id, UpdateEventStatusAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $action->execute($event, 'published');
        return $this->show($id);
    }

    public function cancel($id, UpdateEventStatusAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $action->execute($event, 'cancelled');

        return $this->successResponse(new EventFullResource($event), 'Event cancelled');
    }

    public function updateMessageId(Request $request, $id, UpdateEventMessageAction $action)
    {
        $event = Event::query()->findOrFail($id);
        $action->execute($event, $request->discord_message_id);

        return $this->successResponse(new EventFullResource($event), 'Message ID updated');
    }
}
