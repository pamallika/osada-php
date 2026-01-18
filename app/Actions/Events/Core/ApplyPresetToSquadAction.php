<?php

namespace App\Actions\Events\Core;

use App\Models\EventParticipant;
use App\Models\EventSquad;
use App\Models\Preset;
use Illuminate\Support\Facades\DB;

class ApplyPresetToSquadAction
{
    public function execute(EventSquad $squad, Preset $preset): int
    {
        return DB::transaction(function () use ($squad, $preset) {
            $addedCount = 0;
            $preset->load('users');

            foreach ($preset->users as $user) {
                // Проверяем, не записан ли уже этот юзер на этот ивент (в любой отряд или резерв)
                $exists = EventParticipant::query()->where('event_id', $squad->event_id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$exists) {
                    EventParticipant::create([
                        'event_id' => $squad->event_id,
                        'user_id' => $user->id,
                        'squad_id' => $squad->id,
                        'status' => 'unknown', // Статус для тех, кто добавлен пресетом
                    ]);
                    $addedCount++;
                }
            }

            return $addedCount;
        });
    }
}
