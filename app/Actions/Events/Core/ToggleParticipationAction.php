<?php

namespace App\Actions\Events\Core;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ToggleParticipationAction
{
    public function execute(Event $event, User $user, string $action, ?int $squadId = null)
    {
        return DB::transaction(function () use ($event, $user, $action, $squadId) {
            if ($action === 'decline') {
                return EventParticipant::query()->where('event_id', $event->id)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            $data = ['status' => 'confirmed'];

            if ($action === 'join_squad' && $squadId) {
                $squad = $event->squads()->findOrFail($squadId);

                // Проверка лимита
                if ($squad->participants()->count() >= $squad->slots_limit) {
                    throw ValidationException::withMessages(['squad' => 'В отряде нет мест']);
                }
                $data['squad_id'] = $squadId;
            } else {
                $data['squad_id'] = null; // Резерв
            }

            return EventParticipant::query()->updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $user->id],
                $data
            );
        });
    }
}
