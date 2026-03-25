<?php

namespace App\Actions\Events\Core;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Events\ParticipantUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ToggleParticipationAction
{
    public function execute(Event $event, User $user, string $action, ?int $squadId = null)
    {
        if ($event->status === 'archived') {
            abort(403, 'Cannot change participation in an archived event');
        }

        $result = DB::transaction(function () use ($event, $user, $action, $squadId) {
            $participant = EventParticipant::query()
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->first();

            if ($action === 'decline') {
                if ($participant) {
                    $participant->update([
                        'status' => 'declined',
                        'squad_id' => null
                    ]);
                } else {
                    $participant = EventParticipant::create([
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'status' => 'declined',
                        'squad_id' => null
                    ]);
                }

                broadcast(new ParticipantUpdated($event->id, 'status_changed', [
                    'user_id' => $user->id,
                    'squad_id' => null,
                    'status' => 'declined'
                ]));

                return true;
            }

            // JOIN logic
            $broadcastAction = $participant ? 'moved' : 'joined';

            if (!$squadId) {
                // Если squad_id не передан, ищем системный Резерв
                $reserve = $event->squads()->where('is_system', true)->first();
                $squadId = $reserve?->id;
            }

            $squad = \App\Models\EventSquad::query()->where('event_id', $event->id)
                ->where('id', $squadId)
                ->lockForUpdate()
                ->firstOrFail();

            // Проверка лимита: если slots_limit === 0 (или squad is_system), проверка игнорируется (безлимит)
            if (!$squad->is_system && $squad->slots_limit > 0) {
                $currentCount = $squad->participants()->where('status', 'confirmed')->count();
                if ($currentCount >= $squad->slots_limit) {
                    abort(409, 'Squad is full');
                }
            }

            if ($participant) {
                $participant->update([
                    'status' => 'confirmed',
                    'squad_id' => $squadId
                ]);
            } else {
                $participant = EventParticipant::create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'status' => 'confirmed',
                    'squad_id' => $squadId
                ]);
            }

            broadcast(new ParticipantUpdated($event->id, $broadcastAction, [
                'user_id' => $user->id,
                'squad_id' => $squadId,
                'status' => 'confirmed'
            ]));

            return true;
        });

        if ($result) {
            \App\Jobs\UpdateMessengerEventMessage::dispatch($event->id)->delay(now()->addSeconds(5));
        }

        return $result;
    }
}

