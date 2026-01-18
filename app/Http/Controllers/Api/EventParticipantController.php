<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EventActionRequest;
use App\Actions\Events\Discord\HandleDiscordParticipation;
use App\Http\Resources\Api\Discord\EventFullResource;
use App\Traits\ApiResponser;

class EventParticipantController extends Controller
{
    use ApiResponser;

    public function store(EventActionRequest $request, HandleDiscordParticipation $action)
    {
        // Action сам найдет юзера, ивент и выполнит логику (join/reserve/decline)
        $event = $action->execute($request->validated());

        // Возвращаем полную информацию, чтобы бот обновил Embed
        return $this->successResponse(
            new EventFullResource($event->load(['squads.participants.user.profile', 'participants.user.profile'])),
            'Action processed'
        );
    }
}
