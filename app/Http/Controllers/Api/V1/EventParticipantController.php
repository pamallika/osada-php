<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EventActionRequest;
use App\Actions\Events\Discord\HandleDiscordParticipation;
use App\Http\Resources\Api\Discord\EventFullResource;
use App\Traits\ApiResponser;

class EventParticipantController extends Controller
{
    use ApiResponser;

    public function join($id, \Illuminate\Http\Request $request, \App\Actions\Events\Core\ToggleParticipationAction $action)
    {
        $event = \App\Models\Event::query()->findOrFail($id);
        $user = $request->user();

        $action->execute($event, $user, 'join_squad', $request->squad_id);

        return $this->successResponse([
            'status' => 'confirmed',
            'squad_id' => $request->squad_id
        ], 'Joined successfully');
    }

    public function decline($id, \Illuminate\Http\Request $request, \App\Actions\Events\Core\ToggleParticipationAction $action)
    {
        $event = \App\Models\Event::query()->findOrFail($id);
        $user = $request->user();

        $action->execute($event, $user, 'decline');

        return $this->successResponse(['status' => 'declined'], 'Declined successfully');
    }

    public function move($id, $userId, \Illuminate\Http\Request $request, \App\Actions\Events\Core\ToggleParticipationAction $action)
    {
        $event = \App\Models\Event::query()->findOrFail($id);
        $this->authorize('update', $event);
        
        $user = \App\Models\User::query()->findOrFail($userId);

        $action->execute($event, $user, 'join_squad', $request->squad_id);

        return $this->successResponse(null, 'Participant moved successfully');
    }

    public function store(EventActionRequest $request, HandleDiscordParticipation $action)
    {
        // Action сам найдет пользователя, событие и выполнит логику (join/reserve/decline)
        $event = $action->execute($request->validated());

        // Возвращаем полную информацию, чтобы бот обновил Embed
        return $this->successResponse(
            new EventFullResource($event->load(['squads.participants.user.profile', 'participants.user.profile'])),
            'Action processed'
        );
    }
}
