<?php

namespace App\Actions\Events\Discord;

use App\Actions\Events\Core\ToggleParticipationAction;
use App\Models\Event;
use App\Models\EventSquad;
use App\Models\User;
use App\Models\LinkedAccount;

class HandleDiscordParticipation
{
    public function __construct(
        protected ToggleParticipationAction $coreAction
    ) {}

    public function execute(array $data)
    {
        if (!empty($data['discord_user_id'])) {
            $linkedAccount = LinkedAccount::where('provider', 'discord')
                ->where('provider_id', $data['discord_user_id'])
                ->firstOrFail();
            $user = $linkedAccount->user;
        } else {
            $user = auth()->user();
            if (!$user) {
                abort(401, 'User not authenticated and no discord_user_id provided');
            }
        }

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

        return $event; // Возвращаем событие для ресурса
    }
}
