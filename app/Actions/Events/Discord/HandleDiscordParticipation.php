<?php

namespace App\Actions\Events\Discord;

use App\Actions\Events\Core\ToggleParticipationAction;
use App\Models\Event;
use App\Models\EventSquad;
use App\Models\User;

class HandleDiscordParticipation
{
    public function __construct(
        protected ToggleParticipationAction $coreAction
    ) {}

    public function execute(array $data)
    {
        $user = User::query()->where('discord_id', $data['discord_user_id'])->firstOrFail();

        // Если пришел squad_id, находим event_id через отряд, если он не передан явно
        $event = isset($data['event_id'])
            ? Event::query()->findOrFail($data['event_id'])
            : EventSquad::query()->findOrFail($data['squad_id'])->event;

        $this->coreAction->execute(
            $event,
            $user,
            $data['action'],
            $data['squad_id'] ?? null
        );

        return $event; // Возвращаем ивент для ресурса
    }
}
